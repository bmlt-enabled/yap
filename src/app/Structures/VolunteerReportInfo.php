<?php

namespace App\Structures;

use App\Constants\SpecialPhoneNumber;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;

class VolunteerReportInfo
{
    public $name;
    public $number = SpecialPhoneNumber::UNKNOWN;
    public $gender;
    public $responder = VolunteerResponderOption::UNSPECIFIED;
    public $type = VolunteerType::PHONE;
    public $language;
    public $notes;
    public $shift_info = [];
    public $service_body_id;
}
