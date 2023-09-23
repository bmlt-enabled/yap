<?php

namespace App\Services;

class StubService
{
    public static function timezone()
    {
        return [
            'dstOffset' => 0,
            'rawOffset' => -18000,
            'status' => 'OK',
            'timeZoneId' => 'America/New_York',
            'timeZoneName' => 'Eastern Standard Time'
        ];
    }
}
