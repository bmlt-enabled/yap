<?php

namespace App\Services;

use App\Constants\Http;

class TimeZoneService
{
    protected SettingsService $settings;
    protected HttpService $http;
    private string $timeZoneEndpoint;

    public function __construct(SettingsService $settings, HttpService $http)
    {
        $this->settings = $settings;
        $this->http = $http;
        $this->timeZoneEndpoint = sprintf(
            "https://maps.googleapis.com/maps/api/timezone/json?key=%s",
            $this->settings->get("google_maps_api_key")
        );
    }

    public function getTimeZoneForCoordinates($latitude, $longitude)
    {
        return $this->settings->has("timezone_default")
            ? $this->settings->get("timezone_default")
            : json_decode($this->http->get(sprintf(
                "%s&location=%s,%s&timestamp=%d",
                $this->timeZoneEndpoint,
                $latitude,
                $longitude,
                time() - (time() % 1800)
            ), 3600));
    }
}
