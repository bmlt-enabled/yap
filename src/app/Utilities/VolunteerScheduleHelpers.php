<?php

namespace App\Utilities;

use App\Services\SettingsService;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\App;

class VolunteerScheduleHelpers
{
    public static function dataDecoder($dataString)
    {
        return json_decode(base64_decode($dataString));
    }

    public static function decodeVolunteerData($volunteerData)
    {
        if (isset($volunteerData->volunteer_shift_schedule)) {
            $volunteerData->volunteer_shift_schedule = self::dataDecoder($volunteerData->volunteer_shift_schedule);
            
            // Add localized day names to each schedule item
            if (is_array($volunteerData->volunteer_shift_schedule)) {
                $settings = App::make(SettingsService::class);
                $daysOfWeek = $settings->word('days_of_the_week');
                
                foreach ($volunteerData->volunteer_shift_schedule as $schedule) {
                    if (isset($schedule->day) && isset($daysOfWeek[$schedule->day])) {
                        $schedule->day_name = $daysOfWeek[$schedule->day];
                    }
                }

                usort($volunteerData->volunteer_shift_schedule, function ($a, $b) {
                    return $a->day <=> $b->day;
                });
            }
        }
        return $volunteerData;
    }

    public static function decodeVolunteersCollection($volunteersCollection)
    {
        foreach ($volunteersCollection as $volunteer) {
            $decodedData = json_decode($volunteer->data);
            if (is_array($decodedData) && count($decodedData) > 0) {
                // Decode the shift schedule for each volunteer in the array
                foreach ($decodedData as $volunteerData) {
                    self::decodeVolunteerData($volunteerData);
                }
            }
            $volunteer->data = $decodedData;
        }
        return $volunteersCollection;
    }

    public static function getNameHashColorCode($str): string
    {
        $code = dechex(crc32($str));
        return substr($code, 0, 6);
    }

    public static function getTimezoneList(): array
    {
        return DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC);
    }

    public static function getNextShiftInstance($shift_day, $shift_time, $shift_tz)
    {
        if (isset($shift_tz) && $shift_tz != "" && in_array($shift_tz, self::getTimezoneList())) {
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
