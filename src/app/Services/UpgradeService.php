<?php

namespace App\Services;

use App\Constants\AlertId;
use App\Services\Preflight\PreflightService;
use App\Services\Upgrade\TwilioComplianceService;
use App\Services\Upgrade\UpgradeCheck;
use CurlException;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\RestException;

class UpgradeService extends Service
{
    protected RootServerService $rootServer;
    protected GeocodingService $geocoding;
    protected TimeZoneService $timeZone;
    protected ReportsService $reports;
    protected TwilioService $twilio;
    protected PreflightService $preflight;
    protected TwilioComplianceService $twilioCompliance;

    public function __construct(
        RootServerService $rootServer,
        GeocodingService $geocoding,
        TimeZoneService $timeZone,
        ReportsService $reports,
        TwilioService $twilio,
        PreflightService $preflight,
        TwilioComplianceService $twilioCompliance,
    ) {
        parent::__construct(App::make(SettingsService::class));
        $this->rootServer = $rootServer;
        $this->geocoding = $geocoding;
        $this->timeZone = $timeZone;
        $this->reports = $reports;
        $this->twilio = $twilio;
        $this->preflight = $preflight;
        $this->twilioCompliance = $twilioCompliance;
    }

    public function getStatus()
    {
        $preflight = $this->preflight->run();
        $checks = $this->normalizePreflightChecks($preflight['checks']);

        if (!$preflight['passed']) {
            $failedCheck = collect($checks)->first(
                fn (array $check) => $check['status'] === UpgradeCheck::STATUS_FAIL
            );

            return $this->getState(
                false,
                $failedCheck['message'] ?? 'Preflight checks failed.',
                '',
                $checks
            );
        }

        $warnings = "";
        foreach ($this->settings->minimalRequiredSettings() as $setting) {
            if (!$this->settings->has($setting) || $this->settings->get($setting) === '') {
                return $this->getState(false, "Missing required setting: " . $setting, $warnings, $checks);
            }
        }

        $root_server_settings = $this->rootServer->getServerInfo();

        if (strpos($this->settings->getAdminBMLTRootServer(), 'index.php')) {
            return $this->getState(false, "Your root server points to index.php. Please make sure to set it to just the root directory.", $warnings, $checks);
        }

        if (!isset($root_server_settings)) {
            return $this->getState(false, "Your root server returned no server information.  Double-check that you have the right root server url.", $warnings, $checks);
        } else {
            if ($root_server_settings[0]->semanticAdmin === "0") {
                return $this->getState(false, "Your root server has semanticAdmin disabled, please enable it.  https://bmlt.app/semantic/semantic-administration/", $warnings, $checks);
            }
        }

        foreach ($this->settings->get("digit_map_search_type") as $digit => $value) {
            if ($digit === 0) {
                return $this->getState(false, "You cannot use 0 as an option for `digit_map_search_type`.", $warnings, $checks);
            }
        }

        try {
            $googleapi_settings = $this->geocoding->ping("91409");

            if ($googleapi_settings->status == "REQUEST_DENIED") {
                return $this->getState(false, "Your Google Maps API key came back with the following error. " . $googleapi_settings->error_message. " Please make sure you have the Google Maps Geocoding API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/", $warnings, $checks);
            }

            $timezone_settings = $this->timeZone->getTimeZoneForCoordinates("34.2011137", "-118.475058");

            if ($timezone_settings->status == "REQUEST_DENIED") {
                return $this->getState(false, "Your Google Maps API key came back with the following error. " . $timezone_settings->errorMessage. " Please make sure you have the Google Time Zone API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/", $warnings, $checks);
            }
        } catch (Exception $e) {
            return $this->getState(false, "HTTP Error connecting to Google Maps API, check your network settings.", $warnings, $checks);
        }

        $alerts = $this->reports->getMisconfiguredPhoneNumbersAlerts(AlertId::STATUS_CALLBACK_MISSING);
        if (count($alerts) > 0) {
            $misconfiguredPhoneNumbers = [];
            foreach ($alerts as $alert) {
                $misconfiguredPhoneNumbers[] = $alert->payload;
            }

            $warnings = sprintf("%s is/are phone numbers that are missing Twilio Call Status Changes Callback status.php webhook. This will not allow call reporting to work correctly.  For more information review the documentation page https://yap.bmlt.app/general/reports.", implode(",", $misconfiguredPhoneNumbers));
        }

        $twilioCredentialsValid = false;
        $incomingPhoneNumbers = [];
        try {
            foreach ($this->twilio->client()->incomingPhoneNumbers->read() as $number) {
                $incomingPhoneNumbers[] = $number;
                if (basename($number->voiceUrl)) {
                    if (!strpos($number->voiceUrl, '.php')
                        && !strpos($number->voiceUrl, 'twiml')
                        && !strpos($number->voiceUrl, '/?')
                        && substr($number->voiceUrl, -1) !== "/") {
                        return $this->getState(false, $number->phoneNumber . " webhook should end either with `/` or `/index.php`", $warnings, $checks);
                    }
                }
            }
            $twilioCredentialsValid = true;
        } catch (RestException $e) {
            if ($this->isAllowedError("twilioFakeCredentials")) {
                return $this->getState(false, "Twilio Rest Error: " . $e->getMessage(), $warnings, $checks);
            }
        } catch (ConfigurationException $e) {
            if ($this->isAllowedError("twilioMissingCredentials")) {
                return $this->getState(false, "Twilio Configuration Error: " . $e->getMessage(), $warnings, $checks);
            }
        }

        if ($this->settings->has('smtp_host')) {
            foreach ($this->settings->emailSettings() as $setting) {
                if (!$this->settings->has($setting)) {
                    return $this->getState(false, "Missing required email setting: " . $setting, $warnings, $checks);
                }
            }
        }

        if ($this->settings->has('mysql_hostname')) {
            DB::select("select 1");
        }

        if ($twilioCredentialsValid) {
            $checks = $this->appendTwilioComplianceChecks($checks, $incomingPhoneNumbers);
        }

        return $this->getState(true, "Ready To Yap!", $warnings, $checks);
    }

    /**
     * @param array<int, array<string, mixed>> $preflightChecks
     * @return array<int, array<string, mixed>>
     */
    private function normalizePreflightChecks(array $preflightChecks): array
    {
        return array_map(
            fn (array $check) => UpgradeCheck::fromPreflight($check)->toArray(),
            $preflightChecks
        );
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @param list<object> $incomingPhoneNumbers
     * @return array<int, array<string, mixed>>
     */
    private function appendTwilioComplianceChecks(array $checks, array $incomingPhoneNumbers = []): array
    {
        $complianceChecks = array_map(
            fn (UpgradeCheck $check) => $check->toArray(),
            $this->twilioCompliance->run($this->twilio->client(), $this->settings, $incomingPhoneNumbers)
        );

        return array_merge($checks, $complianceChecks);
    }

    private function getState($status = null, $message = null, $warnings = "", array $checks = [])
    {
        $hasComplianceFail = collect($checks)->contains(
            fn (array $check) => $check['status'] === UpgradeCheck::STATUS_FAIL
        );

        if ($status && $hasComplianceFail) {
            $status = false;
            $failedCheck = collect($checks)->first(
                fn (array $check) => $check['status'] === UpgradeCheck::STATUS_FAIL
            );
            $message = $failedCheck['message'] ?? 'Issues detected';
        }

        $complianceWarnings = collect($checks)
            ->filter(fn (array $check) => $check['status'] === UpgradeCheck::STATUS_WARN
                && in_array($check['id'], TwilioComplianceService::CHECK_IDS, true))
            ->pluck('message')
            ->filter()
            ->values()
            ->all();

        if ($complianceWarnings !== []) {
            $warningText = implode(' ', $complianceWarnings);
            $warnings = $warnings === '' ? $warningText : $warnings . ' ' . $warningText;
        }

        try {
            $build = Storage::get("build.txt");
        } catch (Exception $e) {
            $build = $e->getMessage();
        }
        return [
            "status"=>$status,
            "message"=>$message,
            "warnings"=>$warnings,
            "version"=>$this->settings->version(),
            "build"=>str_replace("\n", "", $build),
            "checks"=>$checks,
        ];
    }

    private function isAllowedError($exceptionName)
    {
        if ($this->settings->has("exclude_errors_on_login_page")) {
            return !in_array($exceptionName, $this->settings->get("exclude_errors_on_login_page"));
        }

        return true;
    }
}
