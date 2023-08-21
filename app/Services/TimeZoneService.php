<?php

namespace App\Services;

use Illuminate\Support\Facades\App;

class TimeZoneService extends Service
{
    protected HttpService $http;
    private string $timeZoneEndpoint;

    public function __construct(HttpService $http)
    {
        parent::__construct(App::make(SettingsService::class));
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
