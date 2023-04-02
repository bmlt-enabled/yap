<?php

namespace App\Utility;

use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;
use DateTime;

class VolunteerRoutingHelpers
{
    public static function checkVolunteerRoutingTime(DateTime $current_time, $volunteers, $v): bool
    {
        return ($current_time >= (new DateTime($volunteers[$v]->start))
            && $current_time <= (new DateTime($volunteers[$v]->end)));
    }

    public static function checkVolunteerRoutingLanguage($volunteer_routing_params, $volunteers, $v): bool
    {
        if ($volunteer_routing_params->volunteer_type == VolunteerType::PHONE) {
            return in_array($volunteer_routing_params->volunteer_language, $volunteers[$v]->language);
        } else {
            # TODO: this does not consider language at the time, we need to think through how to handle things like Gender + Language
            return true;
        }
    }

    public static function checkVolunteerRoutingType($volunteer_routing_params, $volunteers, $v): bool
    {
        return (!isset($volunteers[$v]->type) || str_exists($volunteers[$v]->type, $volunteer_routing_params->volunteer_type));
    }

    public static function checkVolunteerRoutingResponder($volunteer_routing_params, $volunteers, $v): bool
    {
        return ($volunteer_routing_params->volunteer_responder == VolunteerResponderOption::UNSPECIFIED
            || (($volunteer_routing_params->volunteer_responder !== VolunteerResponderOption::UNSPECIFIED
                && isset($volunteers[$v]->responder)
                && $volunteer_routing_params->volunteer_responder == $volunteers[$v]->responder)));
    }

    public static function checkVolunteerRoutingGender($volunteer_routing_params, $volunteers, $v): bool
    {
        return (($volunteer_routing_params->volunteer_gender == VolunteerGender::UNSPECIFIED || $volunteer_routing_params->volunteer_gender == VolunteerGender::NO_PREFERENCE )
            || isset($volunteers[$v]->gender) && $volunteer_routing_params->volunteer_gender == $volunteers[$v]->gender);
    }
}
