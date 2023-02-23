<?php

namespace App\Models;

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;

class VolunteerRoutingParameters
{
    public $service_body_id;
    public $tracker;
    public $cycle_algorithm = CycleAlgorithm::LINEAR_LOOP_FOREVER;
    public $volunteer_type = VolunteerType::PHONE;
    public $volunteer_gender = VolunteerGender::UNSPECIFIED;
    public $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    public $volunteer_language;
}
