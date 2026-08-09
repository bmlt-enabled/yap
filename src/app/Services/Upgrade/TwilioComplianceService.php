<?php

namespace App\Services\Upgrade;

use App\Services\SettingsService;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

class TwilioComplianceService
{
    public const CHECK_IDS = [
        'twilio_account_type',
        'voice_geo_us',
        'trust_hub_profile',
        'sms_a2p_brand',
        'toll_free_verification',
    ];

    private const VOICE_GEO_CONSOLE_URL = 'https://www.twilio.com/console/voice/calls/geo-permissions';
    private const TRUST_HUB_CONSOLE_URL = 'https://www.twilio.com/console/trust-hub/customer-profiles';
    private const A2P_CONSOLE_URL = 'https://www.twilio.com/console/sms/a2p-brand-registration';
    private const TOLL_FREE_CONSOLE_URL = 'https://www.twilio.com/console/sms/toll-free-verification';
    private const ACCOUNT_CONSOLE_URL = 'https://www.twilio.com/console/account/settings';

    /**
     * @param list<object> $incomingPhoneNumbers
     * @return list<UpgradeCheck>
     */
    public function run(Client $client, SettingsService $settings, array $incomingPhoneNumbers = []): array
    {
        return [
            $this->checkAccountType($client),
            $this->checkUsVoiceGeoPermissions($client),
            $this->checkTrustHubProfile($client),
            $this->checkSmsA2pBrand($client, $settings),
            $this->checkTollFreeVerification($client, $incomingPhoneNumbers),
        ];
    }

    private function checkAccountType(Client $client): UpgradeCheck
    {
        try {
            $account = $client->api->v2010->account->fetch();

            if (strcasecmp((string) $account->type, 'Trial') === 0) {
                return UpgradeCheck::warn(
                    'twilio_account_type',
                    'Twilio account type',
                    'This is a Trial account. Outbound calls are limited to verified numbers.',
                    self::ACCOUNT_CONSOLE_URL,
                );
            }

            return UpgradeCheck::pass(
                'twilio_account_type',
                'Twilio account type',
                sprintf('Account type is %s.', $account->type),
            );
        } catch (RestException $e) {
            return UpgradeCheck::skip(
                'twilio_account_type',
                'Twilio account type',
                'Unable to verify account type: ' . $e->getMessage(),
            );
        }
    }

    private function checkUsVoiceGeoPermissions(Client $client): UpgradeCheck
    {
        try {
            $country = $client->voice->v1->dialingPermissions->countries('US')->fetch();

            if (!$country->lowRiskNumbersEnabled) {
                return UpgradeCheck::fail(
                    'voice_geo_us',
                    'US voice geo permissions',
                    'Low-risk US dialing is not enabled. Volunteer outbound calls to US numbers will fail.',
                    self::VOICE_GEO_CONSOLE_URL,
                );
            }

            return UpgradeCheck::pass(
                'voice_geo_us',
                'US voice geo permissions',
                'Low-risk US dialing is enabled.',
            );
        } catch (RestException $e) {
            return UpgradeCheck::skip(
                'voice_geo_us',
                'US voice geo permissions',
                'Unable to verify US voice geo permissions: ' . $e->getMessage(),
            );
        }
    }

    private function checkTrustHubProfile(Client $client): UpgradeCheck
    {
        try {
            $profiles = $client->trusthub->v1->customerProfiles->read([], 20);

            foreach ($profiles as $profile) {
                if (strcasecmp((string) $profile->status, 'twilio-approved') === 0) {
                    return UpgradeCheck::pass(
                        'trust_hub_profile',
                        'Trust Hub profile',
                        'An approved customer profile was found.',
                    );
                }
            }

            return UpgradeCheck::warn(
                'trust_hub_profile',
                'Trust Hub profile',
                'No approved Primary Customer Profile found. Outbound calls to unverified numbers may fail.',
                self::TRUST_HUB_CONSOLE_URL,
            );
        } catch (RestException $e) {
            return UpgradeCheck::skip(
                'trust_hub_profile',
                'Trust Hub profile',
                'Unable to verify Trust Hub profile: ' . $e->getMessage(),
            );
        }
    }

    private function checkSmsA2pBrand(Client $client, SettingsService $settings): UpgradeCheck
    {
        if ($this->isSmsDisabled($settings)) {
            return UpgradeCheck::skip(
                'sms_a2p_brand',
                'SMS registration',
                'SMS meeting results are disabled; A2P brand registration was not checked.',
            );
        }

        try {
            $brands = $client->messaging->v1->brandRegistrations->read([], 20);

            foreach ($brands as $brand) {
                if (strcasecmp((string) $brand->status, 'APPROVED') === 0) {
                    return UpgradeCheck::pass(
                        'sms_a2p_brand',
                        'SMS registration',
                        'An approved A2P brand registration was found.',
                    );
                }
            }

            return UpgradeCheck::fail(
                'sms_a2p_brand',
                'SMS registration',
                'No approved A2P brand registration found. SMS meeting results may fail to deliver.',
                self::A2P_CONSOLE_URL,
            );
        } catch (RestException $e) {
            return UpgradeCheck::skip(
                'sms_a2p_brand',
                'SMS registration',
                'Unable to verify A2P brand registration: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @param list<object> $incomingPhoneNumbers
     */
    private function checkTollFreeVerification(Client $client, array $incomingPhoneNumbers): UpgradeCheck
    {
        try {
            $numbers = $incomingPhoneNumbers !== []
                ? $incomingPhoneNumbers
                : $client->incomingPhoneNumbers->read();

            $tollFreeNumbers = [];
            foreach ($numbers as $number) {
                $phoneNumber = $number->phoneNumber ?? null;
                $sid = $number->sid ?? null;
                if ($phoneNumber === null || $sid === null) {
                    continue;
                }

                if ($this->isTollFreeNumber((string) $phoneNumber)) {
                    $tollFreeNumbers[$sid] = (string) $phoneNumber;
                }
            }

            if ($tollFreeNumbers === []) {
                return UpgradeCheck::skip(
                    'toll_free_verification',
                    'Toll-free verification',
                    'No toll-free phone numbers are configured.',
                );
            }

            $verificationsByPhoneSid = [];
            foreach ($client->messaging->v1->tollfreeVerifications->read([], 50) as $verification) {
                if (!empty($verification->tollfreePhoneNumberSid)) {
                    $verificationsByPhoneSid[$verification->tollfreePhoneNumberSid] = (string) $verification->status;
                }
            }

            $unverified = [];
            foreach ($tollFreeNumbers as $sid => $phoneNumber) {
                $status = $verificationsByPhoneSid[$sid] ?? null;
                if ($status === null || strcasecmp($status, 'TWILIO_APPROVED') !== 0) {
                    $unverified[] = $phoneNumber;
                }
            }

            if ($unverified !== []) {
                return UpgradeCheck::warn(
                    'toll_free_verification',
                    'Toll-free verification',
                    sprintf(
                        'Toll-free number(s) %s are not verified for SMS.',
                        implode(', ', $unverified),
                    ),
                    self::TOLL_FREE_CONSOLE_URL,
                );
            }

            return UpgradeCheck::pass(
                'toll_free_verification',
                'Toll-free verification',
                'All toll-free numbers are verified for SMS.',
            );
        } catch (RestException $e) {
            return UpgradeCheck::skip(
                'toll_free_verification',
                'Toll-free verification',
                'Unable to verify toll-free numbers: ' . $e->getMessage(),
            );
        } catch (\Throwable $e) {
            return UpgradeCheck::skip(
                'toll_free_verification',
                'Toll-free verification',
                'Unable to verify toll-free numbers: ' . $e->getMessage(),
            );
        }
    }

    private function isSmsDisabled(SettingsService $settings): bool
    {
        if (!$settings->has('sms_disable')) {
            return false;
        }

        return (bool) json_decode((string) $settings->get('sms_disable'));
    }

    private function isTollFreeNumber(string $phoneNumber): bool
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if ($digits === null) {
            return false;
        }

        if (str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }

        return (bool) preg_match('/^(800|888|877|866|855|844|833)/', $digits);
    }
}
