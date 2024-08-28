<?php

namespace App\Utilities;

use App\Services\SettingsService;
use DateTime;

class VolunteerScheduleHelpers
{
    public static function dataDecoder($dataString)
    {
        return json_decode(base64_decode($dataString));
    }

    public static function getNameHashColorCode($str): string
    {
        $code = dechex(crc32($str));
        return substr($code, 0, 6);
    }

    public static function getNextShiftInstance($shift_day, $shift_time, $shift_tz)
    {
        if (isset($shift_tz) && $shift_tz != "") {
            date_default_timezone_set($shift_tz);
        }
        $mod_meeting_day = (new DateTime())
            ->modify(SettingsService::$dateCalculationsMap[$shift_day])->format("Y-m-d");
        $mod_meeting_datetime = (new DateTime($mod_meeting_day . " " . $shift_time));
        return str_replace(" ", "T", $mod_meeting_datetime->format("Y-m-d H:i:s"));
    }

    public static function filterOutPhoneNumber($volunteers): array
    {
        $volunteers_array = $volunteers;
        for ($v = 0; $v < count($volunteers_array); $v++) {
            unset($volunteers_array[$v]->contact);
        }

        return $volunteers_array;
    }
}
