<?php

namespace App\Services;

use App\Constants\CycleAlgorithm;
use App\Constants\SpecialPhoneNumber;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Exceptions\NoVolunteersException;
use App\Models\ConfigData;
use App\Structures\Volunteer;
use App\Structures\VolunteerInfo;
use App\Structures\VolunteerReportInfo;
use App\Utilities\VolunteerRoutingHelpers;
use App\Utilities\VolunteerScheduleHelpers;
use DateTime;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class VolunteerService extends Service
{
    protected RootServerService $rootServerService;

    public function __construct(RootServerService $rootServerService)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->rootServerService = $rootServerService;
    }

    public function getHelplineSchedule($service_body_int, $filtered = false): array
    {
        $volunteers = $this->getVolunteers($service_body_int);
        if (count($volunteers) > 0) {
            $finalSchedule = $this->getVolunteerInfo($volunteers);

            usort($finalSchedule, function ($a, $b) {
                return $a->sequence > $b->sequence ? 1 : -1;
            });

            return $filtered ? VolunteerScheduleHelpers::filterOutPhoneNumber($finalSchedule) : $finalSchedule;
        } else {
            throw new NoVolunteersException();
        }
    }

    public function getVolunteers($serviceBodyId, $recurse = false): array
    {
        if ($recurse) {
            $serviceBodyIds = $this->rootServerService->getServiceBodiesForUserRecursively($serviceBodyId);
            $volunteerData = ConfigData::getVolunteersRecursively($serviceBodyIds);
        } else {
            $volunteerData = ConfigData::getVolunteers($serviceBodyId);
        }

        $volunteerList = [];
        foreach ($volunteerData as $volunteerDatum) {
            if (count($volunteerData) > 0) {
                $volunteers = json_decode($volunteerDatum->data);
                for ($v = 0; $v < count($volunteers); $v++) {
                    if (isset($volunteers[$v]->group_id) && isset($volunteers[$v]->group_enabled)
                    && json_decode($volunteers[$v]->group_enabled)) {
                        $groupVolunteers = $this->getGroupVolunteers($volunteers[$v]->group_id);
                        foreach ($groupVolunteers as $groupVolunteer) {
                            $groupVolunteer->service_body_id = $volunteerDatum->service_body_id;
                            $volunteerList[] = $groupVolunteer;
                        }
                    } else {
                        $volunteers[$v]->service_body_id = $volunteerDatum->service_body_id;
                        $volunteerList[] = $volunteers[$v];
                    }
                }
            }
        }

        return $volunteerList;
    }

    public function getVolunteersListReport($service_body_int, $recurse = false)
    {
        $volunteers = $this->getVolunteers($service_body_int, $recurse);
        if (count($volunteers) > 0) {
            $finalSchedule = $this->getAllVolunteersList($volunteers);

            return $finalSchedule;
        } else {
            throw new NoVolunteersException();
        }
    }

    public function getHelplineVolunteersActiveNow($volunteer_routing_params): array
    {
        try {
            $volunteers = $this->getHelplineSchedule($volunteer_routing_params->service_body_id);
            $activeNow = [];
            for ($v = 0; $v < count($volunteers); $v++) {
                if (isset($volunteers[$v]->time_zone) && $volunteers[$v]->time_zone !== "") {
                    date_default_timezone_set($volunteers[$v]->time_zone);
                }
                $current_time = new DateTime();
                if (VolunteerRoutingHelpers::checkVolunteerRoutingTime($current_time, $volunteers, $v)
                    && VolunteerRoutingHelpers::checkVolunteerRoutingType($volunteer_routing_params, $volunteers, $v)
                    && VolunteerRoutingHelpers::checkVolunteerRoutingGender($volunteer_routing_params, $volunteers, $v)
                    && VolunteerRoutingHelpers::checkVolunteerRoutingLanguage($volunteer_routing_params, $volunteers, $v)
                    && VolunteerRoutingHelpers::checkVolunteerRoutingResponder($volunteer_routing_params, $volunteers, $v)) {
                    $activeNow[] = $volunteers[$v];
                }
            }

            return $activeNow;
        } catch (NoVolunteersException $nve) {
            throw $nve;
        }
    }

    public function getHelplineVolunteer($volunteer_routing_params)
    {
        try {
            $volunteers = $this->getHelplineVolunteersActiveNow($volunteer_routing_params);
            Log::debug("getHelplineVolunteer():: activeVolunteers: " . var_export($volunteers, true));
            if (isset($volunteers) && count($volunteers) > 0) {
                if ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL) {
                    if ($volunteer_routing_params->tracker > count($volunteers) - 1) {
                        return new Volunteer(SpecialPhoneNumber::VOICE_MAIL);
                    }

                    return new Volunteer($volunteers[$volunteer_routing_params->tracker]->contact, $volunteers[$volunteer_routing_params->tracker]);
                } elseif ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::LINEAR_LOOP_FOREVER) {
                    $volunteer = $volunteers[$volunteer_routing_params->tracker % count($volunteers)];
                    return new Volunteer($volunteer->contact, $volunteer);
                } elseif ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::RANDOM_LOOP_FOREVER) {
                    $volunteer = $volunteers[rand(0, count($volunteers) - 1)];
                    return new Volunteer($volunteer->contact, $volunteer);
                } elseif ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::RANDOM_CYCLE_AND_VOICEMAIL) {
                    if (!session()->has('volunteers_randomized')) {
                        shuffle($volunteers);
                        session()->put('volunteers_randomized', $volunteers);
                    }

                    $volunteers = session()->get('volunteers_randomized');

                    if ($volunteer_routing_params->tracker > count($volunteers) - 1) {
                        return new Volunteer(SpecialPhoneNumber::VOICE_MAIL);
                    }

                    return new Volunteer($volunteers[$volunteer_routing_params->tracker]->contact, $volunteers[$volunteer_routing_params->tracker]);
                } elseif ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::BLASTING) {
                    $volunteers_numbers = [];

                    foreach ($volunteers as $volunteer) {
                        $volunteers_numbers[] = $volunteer->contact;
                    }

                    return new Volunteer(join(",", $volunteers_numbers));
                }
            } else {
                return new Volunteer(SpecialPhoneNumber::UNKNOWN);
            }
        } catch (NoVolunteersException $nve) {
            return new Volunteer(SpecialPhoneNumber::UNKNOWN);
        }
    }

    public function getAllVolunteersList($volunteers): array
    {
        $finalSchedule = [];

        for ($v = 0; $v < count($volunteers); $v++) {
            $volunteer = $volunteers[$v];
            $volunteerInfo = new VolunteerReportInfo();
            $volunteerInfo->name = $volunteer->volunteer_name
                . (isset($volunteer->volunteer_gender) ? " " . VolunteerGender::getGenderById($volunteer->volunteer_gender) : "")
                . (isset($volunteer->volunteer_language) ? " " . json_encode($volunteer->volunteer_language) : "");
            $volunteerInfo->shift_info = VolunteerScheduleHelpers::dataDecoder($volunteer->volunteer_shift_schedule);
            $volunteerInfo->number = $volunteer->volunteer_phone_number;
            $volunteerInfo->gender = $volunteer->volunteer_gender ?? VolunteerGender::UNSPECIFIED;
            $volunteerInfo->responder = $volunteer->volunteer_responder ?? VolunteerResponderOption::UNSPECIFIED;
            $volunteerInfo->service_body_id = $volunteer->service_body_id;
            $volunteerInfo->notes = $volunteer->volunteer_notes ?? "";
            if (strlen($this->settings->get('language_selections')) > 0) {
                if (property_exists($volunteer, 'volunteer_language')) {
                    $volunteerInfo->language = $volunteer->volunteer_language;
                } else {
                    $volunteerInfo->language = array(explode(',', $this->settings->get('language_selections'))[0]);
                }
            } else {
                $volunteerInfo->language = array($this->settings->get("language"));
            }

            $finalSchedule[] = $volunteerInfo;
        }

        return $finalSchedule;
    }

    public function getGroupsForServiceBody($service_body_id, $manage = false)
    {
        $all_groups = ConfigData::getAllGroups();
        $final_groups = array();
        $service_body_id = intval($service_body_id); // Ensure consistent type

        foreach ($all_groups as $all_group) {
            $group_service_body_id = intval($all_group->service_body_id); // Ensure consistent type
            $group_data = json_decode($all_group->data);

            if ($group_service_body_id === $service_body_id
                || (!$manage
                    && isset($group_data[0]->group_shared_service_bodies)
                    && in_array((string)$service_body_id, $group_data[0]->group_shared_service_bodies))) {
                $final_groups[] = $all_group;
            }
        }

        return $final_groups;
    }

    public function getGroupVolunteers($group_id)
    {
        $groupData = ConfigData::getGroupVolunteers($group_id);
        return isset($groupData[0]->data) ? json_decode($groupData[0]->data) : array();
    }

    private function getVolunteerInfo($volunteers): array
    {
        $finalSchedule = [];

        for ($v = 0; $v < count($volunteers); $v++) {
            $volunteer = $volunteers[$v];
            if (isset($volunteer->volunteer_enabled) && $volunteer->volunteer_enabled &&
                isset($volunteer->volunteer_phone_number) && strlen($volunteer->volunteer_phone_number) > 0) {
                $volunteerShiftSchedule = VolunteerScheduleHelpers::dataDecoder($volunteer->volunteer_shift_schedule);
                foreach ($volunteerShiftSchedule as $vsi) {
                    $volunteerInfo = new VolunteerInfo();
                    $volunteerInfo->type = isset($vsi->type) ? $vsi->type : $volunteerInfo->type;
                    $volunteerInfo->title = $volunteer->volunteer_name . " (" . $volunteerInfo->type . ")"
                        . (isset($volunteer->volunteer_gender) ? " " . VolunteerGender::getGenderById($volunteer->volunteer_gender) : "")
                        . (isset($volunteer->volunteer_language) ? " " . json_encode($volunteer->volunteer_language) : "");
                    $volunteerInfo->time_zone = $vsi->tz;
                    $volunteerInfo->start = VolunteerScheduleHelpers::getNextShiftInstance($vsi->day, $vsi->start_time, $volunteerInfo->time_zone);
                    $volunteerInfo->end = VolunteerScheduleHelpers::getNextShiftInstance($vsi->day, $vsi->end_time, $volunteerInfo->time_zone);
                    $volunteerInfo->weekday_id = $vsi->day;
                    $volunteerInfo->weekday = $this->settings->word('days_of_the_week')[$vsi->day];
                    $volunteerInfo->sequence = $v;
                    $volunteerInfo->contact = $volunteer->volunteer_phone_number;
                    $volunteerInfo->color = "#" . VolunteerScheduleHelpers::getNameHashColorCode(strval($v + 1) . "-" . $volunteerInfo->title);
                    $volunteerInfo->gender = isset($volunteer->volunteer_gender) ? $volunteer->volunteer_gender : VolunteerGender::UNSPECIFIED;
                    $volunteerInfo->responder = isset($volunteer->volunteer_responder) ? $volunteer->volunteer_responder : VolunteerResponderOption::UNSPECIFIED;
                    if (strlen($this->settings->get('language_selections')) > 0) {
                        $volunteerInfo->language = property_exists($volunteer, 'volunteer_language') ? $volunteer->volunteer_language : array(explode(',', $this->settings->get('language_selections'))[0]);
                    } else {
                        $volunteerInfo->language = array($this->settings->get("language"));
                    }
                    $finalSchedule[] = $volunteerInfo;
                }
            }
        }

        return $finalSchedule;
    }
}
