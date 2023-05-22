<?php

namespace App\Services;

use App\Constants\DataType;
use App\Constants\SpecialPhoneNumber;
use App\Repositories\ConfigRepository;
use App\Models\ServiceBodyCallHandling;

class ConfigService
{
    protected ConfigRepository $config;

    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    public function getCallHandling($serviceBodyId)
    {
        $helplineData = $this->config->getDbData($serviceBodyId, DataType::YAP_CALL_HANDLING_V2);
        // TODO: this line needs to be reworked after functions.php is blown up
        return count($helplineData) > 0 ? $this->getServiceBodyCallHandlingData(json_decode(json_encode($helplineData[0]), true))
            : $this->getServiceBodyCallHandlingData(null);
    }

    public function getServiceBodyCallHandling($service_body_id)
    {
        $helplineData = $this->config->getDbData($service_body_id, DataType::YAP_CALL_HANDLING_V2);
        return count($helplineData) > 0 ?
            $this->getServiceBodyCallHandlingData($helplineData[0]) : $this->getServiceBodyCallHandlingData(null);
    }

    public function getServiceBodyCallHandlingData($helplineData)
    {
        $config = new ServiceBodyCallHandling();
        if (isset($helplineData)) {
            $data = json_decode($helplineData['data'])[0];
            if (isset($data)) {
                $config->service_body_id = $helplineData['service_body_id'];

                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'override_') && strlen($value) > 0) {
                        $_SESSION[$key] = $value;
                    }
                }

                $config->volunteer_routing_enabled = str_contains($data->volunteer_routing, "volunteers");
                $config->volunteer_routing_redirect = $data->volunteer_routing == "volunteers_redirect";
                $config->volunteer_routing_redirect_id = $config->volunteer_routing_redirect ?
                    $data->volunteers_redirect_id : 0;
                $config->forced_caller_id_enabled = isset($data->forced_caller_id)
                    && strlen($data->forced_caller_id) > 0;
                $config->forced_caller_id_number = $config->forced_caller_id_enabled ? $data->forced_caller_id :
                    SpecialPhoneNumber::UNKNOWN;
                $config->call_timeout = isset($data->call_timeout) && strlen($data->call_timeout > 0) ?
                    intval($data->call_timeout) : 20;
                $config->volunteer_sms_notification_enabled = isset($data->volunteer_sms_notification) &&
                    $data->volunteer_sms_notification != "no_sms";
                $config->gender_routing_enabled = isset($data->gender_routing) && intval($data->gender_routing) == 1;
                $config->call_strategy = isset($data->call_strategy) ?
                    intval($data->call_strategy) : $config->call_strategy;
                $config->primary_contact_number_enabled = isset($data->primary_contact)
                    && strlen($data->primary_contact) > 0;
                $config->primary_contact_number = $config->primary_contact_number_enabled ? $data->primary_contact : "";
                $config->primary_contact_email_enabled = isset($data->primary_contact_email)
                    && strlen($data->primary_contact_email) > 0;
                $config->primary_contact_email = $config->primary_contact_email_enabled
                    ? $data->primary_contact_email : "";
                $config->moh = isset($data->moh) && strlen($data->moh) > 0 ? $data->moh : $config->moh;
                $config->moh_count = count(explode(",", $config->moh));
                $config->sms_routing_enabled = $data->volunteer_routing == "volunteers_and_sms";
                $config->sms_strategy = isset($data->sms_strategy) ?
                    intval($data->sms_strategy) : $config->sms_strategy;
            }
        }

        return $config;
    }
}
