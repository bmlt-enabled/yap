<?php

namespace App\Models;

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;

class VolunteerRoutingParameters
{
    public string $service_body_id;
    public int $tracker;
    public int $cycle_algorithm = CycleAlgorithm::LINEAR_LOOP_FOREVER;
    public string $volunteer_type = VolunteerType::PHONE;
    public int $volunteer_gender = VolunteerGender::UNSPECIFIED;
    public int $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    public string $volunteer_language;
}
