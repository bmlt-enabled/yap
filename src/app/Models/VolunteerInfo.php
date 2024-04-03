<?php

namespace App\Models;

use App\Constants\SpecialPhoneNumber;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;

class VolunteerInfo
{
    public $title;
    public $start;
    public $end;
    public $weekday_id;
    public $weekday;
    public $sequence;
    public $time_zone;
    public $contact = SpecialPhoneNumber::UNKNOWN;
    public $color;
    public $gender;
    public $responder = VolunteerResponderOption::UNSPECIFIED;
    public $type = VolunteerType::PHONE;
    public $language;
}
