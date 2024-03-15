<?php

namespace App\Services;

class ConferenceService
{
    public function getConferenceName($serviceBodyId, $random = true): string
    {
        if ($random) {
            return $serviceBodyId . "_" . rand(1000000, 9999999) . "_" . time();
        } else {
            return $serviceBodyId . "_static_room";
        }
    }
}
