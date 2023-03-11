<?php

namespace App\Models;

class VolunteerReportInfo
{
    public $name;
    public $number = SpecialPhoneNumber::UNKNOWN;
    public $gender;
    public $responder = VolunteerResponderOption::UNSPECIFIED;
    public $type = VolunteerType::PHONE;
    public $language;
    public $shift_info = [];
}
