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

    public function get247Schedule()
    {
        $shiftTz = "America/New_York";
        $shiftStart = "12:00 AM";
        $shiftEnd = "11:59 PM";

        $shifts = [];
        for ($i = 1; $i <= 7; $i++) {
            $shifts[] = [
                "day" => $i,
                "tz" => $shiftTz,
                "start_time" => $shiftStart,
                "end_time" => $shiftEnd,
            ];
        }

        return base64_encode(json_encode($shifts));
    }
}
