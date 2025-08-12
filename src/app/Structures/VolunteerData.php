<?php

namespace App\Structures;

class VolunteerData extends Structure
{
    public string $volunteer_name;
    public string $volunteer_phone_number;
    public int $volunteer_gender;
    public int $volunteer_responder;
    public array $volunteer_languages;
    public string $volunteer_notes;
    public bool $volunteer_enabled;
    public string $volunteer_shift_schedule;

    public function __construct($volunteer = null)
    {
        if ($volunteer) {
            // Dynamically assign all properties from the passed group object
            foreach (get_object_vars($volunteer) as $property => $value) {
                if ($property == "volunteer_shift_schedule") {
                    $this->$property = \App\Utilities\VolunteerScheduleHelpers::dataDecoder($value);
                } else {
                    $this->$property = $value;
                }
            }
        } else {
            // Optionally, set default values here
            $this->volunteer_name = "";
            $this->volunteer_phone_number = "";
            $this->volunteer_responder = false;
            $this->volunteer_languages = [];
            $this->volunteer_notes = "";
            $this->volunteer_enabled = false;
            $this->volunteer_shift_schedule = "";
        }
    }
}
