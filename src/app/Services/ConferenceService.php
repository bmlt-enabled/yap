<?php

namespace App\Services;

class ConferenceService
{
    public function getConferenceName($serviceBodyId): string
    {
        return $serviceBodyId . "_" . rand(1000000, 9999999) . "_" . time();
    }
}
