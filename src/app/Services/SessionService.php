<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use stdClass;

class SessionService extends Service
{
    protected ConfigService $config;
    protected RootServerService $rootServer;

    public function __construct(ConfigService $config, RootServerService $rootServer)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->config = $config;
        $this->rootServer = $rootServer;
    }

    public function setConfigForService($service_body_id): void
    {
        if (intval($service_body_id) > 0) {
            $service_body_config = $this->getServiceBodyConfig($service_body_id);

            if (isset($service_body_config)) {
                foreach ($service_body_config as $item => $value) {
                    // Skip setting "twilio_account_sid" and "twilio_auth_token" if "call_state" is in the session
                    if (($item == "twilio_account_sid" || $item == "twilio_auth_token") && session()->has('call_state')) {
                        continue;
                    }
                    session()->put("override_" . $item, $value);
                }
            }
        }
    }

    private function getServiceBodyConfig($service_body_id): ?stdClass
    {
        $lookup_id = $service_body_id;
        $config = new StdClass();

        while (true) {
            $config_from_db = $this->config->getConfig($lookup_id);
            if (isset($config_from_db)) {
                $config_obj = json_decode($config_from_db->data);
                foreach ($this->settings->allowlist() as $setting => $value) {
                    if (isset($config_obj[0]->$setting) && !isset($config->$setting)) {
                        if (gettype($value['default']) === "array") {
                            $config->$setting = (array) json_decode(str_replace("'", "\"", $config_obj[0]->$setting));
                        } else {
                            $config->$setting = $config_obj[0]->$setting;
                        }
                    }
                }
            }

            $found_service_body = $this->rootServer->getServiceBody($lookup_id);
            if (!isset($found_service_body)) {
                return null;
            }
            $lookup_id = $found_service_body->parent_id;
            if ($lookup_id == 0) {
                return $config;
            }
        }
    }
}
