<?php

namespace App\Models;

class VolunteerData
{
    public string $volunteer_name;
    public string $volunteer_phone_number;
    public int $volunteer_gender;
    public int $volunteer_responder;
    public array $volunteer_languages;
    public string $volunteer_notes;
    public bool $volunteer_enabled;
    public string $volunteer_shift_schedule;
}
