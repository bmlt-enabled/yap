<?php

namespace Tests\Support;

class TwilioComplianceMocks
{
    public static function apply(object $twilioClient, array $overrides = []): void
    {
        $account = mock('\Twilio\Rest\Api\V2010\AccountInstance');
        $account->type = $overrides['account']['type'] ?? 'Full';
        $accountContext = mock('\Twilio\Rest\Api\V2010\AccountContext');
        $accountContext->shouldReceive('fetch')->andReturn($account);
        $twilioClient->api = (object) ['v2010' => (object) ['account' => $accountContext]];

        $usCountry = mock('\Twilio\Rest\Voice\V1\DialingPermissions\CountryInstance');
        $usCountry->lowRiskNumbersEnabled = $overrides['usCountry']['lowRiskNumbersEnabled'] ?? true;
        $countryContext = mock('\Twilio\Rest\Voice\V1\DialingPermissions\CountryContext');
        $countryContext->shouldReceive('fetch')->andReturn($usCountry);
        $dialingPermissionsContext = mock('\Twilio\Rest\Voice\V1\DialingPermissionsList');
        $dialingPermissionsContext->shouldReceive('countries')->with('US')->andReturn($countryContext);
        $twilioClient->voice = (object) [
            'v1' => (object) [
                'dialingPermissions' => $dialingPermissionsContext,
            ],
        ];

        $profiles = $overrides['profiles'] ?? null;
        if ($profiles === null) {
            $approvedProfile = mock('\Twilio\Rest\Trusthub\V1\CustomerProfilesInstance');
            $approvedProfile->status = 'twilio-approved';
            $profiles = [$approvedProfile];
        }
        $customerProfilesContext = mock('\Twilio\Rest\Trusthub\V1\CustomerProfilesList');
        $customerProfilesContext->shouldReceive('read')->andReturn($profiles);
        $twilioClient->trusthub = (object) ['v1' => (object) ['customerProfiles' => $customerProfilesContext]];

        $brands = $overrides['brands'] ?? null;
        if ($brands === null) {
            $approvedBrand = mock('\Twilio\Rest\Messaging\V1\BrandRegistrationInstance');
            $approvedBrand->status = 'APPROVED';
            $brands = [$approvedBrand];
        }
        $brandRegistrationsContext = mock('\Twilio\Rest\Messaging\V1\BrandRegistrationsList');
        $brandRegistrationsContext->shouldReceive('read')->andReturn($brands);
        $tollfreeVerificationsContext = mock('\Twilio\Rest\Messaging\V1\TollfreeVerificationsList');
        $tollfreeVerificationsContext->shouldReceive('read')->andReturn($overrides['tollfreeVerifications'] ?? []);
        $twilioClient->messaging = (object) [
            'v1' => (object) [
                'brandRegistrations' => $brandRegistrationsContext,
                'tollfreeVerifications' => $tollfreeVerificationsContext,
            ],
        ];
    }
}
