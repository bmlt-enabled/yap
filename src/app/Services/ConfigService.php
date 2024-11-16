<?php

namespace App\Services;

use App\Constants\SpecialPhoneNumber;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Structures\ServiceBodyCallHandling;

class ConfigService
{
    protected RootServerService $rootServer;

    public function __construct(RootServerService $rootServer)
    {
        $this->rootServer = $rootServer;
    }

    public function getConfig($service_body_id)
    {
        $configs = ConfigData::getAllServiceBodyConfiguration();
        foreach ($configs as $config) {
            if ($config->service_body_id == $service_body_id) {
                return $config;
            }
        }

        return null;
    }

    public function getCallHandling($serviceBodyId): ServiceBodyCallHandling
    {
        $helplineData = ConfigData::getCallHandling(intval($serviceBodyId));
        return count($helplineData) > 0 ? $this->getServiceBodyCallHandlingData($helplineData[0])
            : $this->getServiceBodyCallHandlingData(null);
    }

    public function getVolunteerRoutingEnabledServiceBodies(): array
    {
        $all_helpline_data = ConfigData::getAllCallHandling();
        $service_bodies = $this->rootServer->getServiceBodiesForUser();
        $helpline_enabled = array();

        for ($x = 0; $x < count($all_helpline_data); $x++) {
            $config = $this->getServiceBodyCallHandlingData($all_helpline_data[$x], false);
            if ($config->volunteer_routing_enabled || $config->sms_routing_enabled) {
                for ($y = 0; $y < count($service_bodies); $y++) {
                    if ($config->service_body_id == intval($service_bodies[$y]->id)) {
                        $config->service_body_name = $service_bodies[$y]->name;
                        $config->service_body_parent_id = $service_bodies[$y]->parent_id;
                        $config->service_body_parent_name = $service_bodies[$y]->parent_name;
                        $helpline_enabled[] = $config;
                    }
                }
            }
        }

        return $helpline_enabled;
    }

    public function getServiceBodyCallHandlingData($helplineData, $setOverrides = true): ServiceBodyCallHandling
    {
        $config = new ServiceBodyCallHandling();
        if (isset($helplineData)) {
            $data = json_decode($helplineData->data)[0];
            if (isset($data)) {
                $config->service_body_id = $helplineData->service_body_id;

                if ($setOverrides) {
                    foreach ($data as $key => $value) {
                        if (str_starts_with($key, 'override_') && strlen($value) > 0) {
                            $_SESSION[$key] = $value;
                        }
                    }
                }

                $config->volunteer_routing_enabled = str_contains($data->volunteer_routing, VolunteerRoutingType::VOLUNTEERS);
                $config->volunteer_routing_redirect = $data->volunteer_routing == VolunteerRoutingType::VOLUNTEERS_REDIRECT;
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
                $config->sms_routing_enabled = $data->volunteer_routing == VolunteerRoutingType::VOLUNTEERS_AND_SMS;
                $config->sms_strategy = isset($data->sms_strategy) ?
                    intval($data->sms_strategy) : $config->sms_strategy;
            }
        }

        return $config;
    }

    public function getUsers()
    {
        return $this->users->getUsers();
    }
}
