<?php

namespace App\Structures;

class Volunteer extends Structure
{
    public $phoneNumber;
    public $volunteerInfo;

    public function __construct($phoneNumber, $volunteerInfo = null)
    {
        $this->phoneNumber = $phoneNumber;
        $this->volunteerInfo = $volunteerInfo;
    }
}
