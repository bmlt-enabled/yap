<?php

namespace App\Services;

use Illuminate\Support\Facades\App;

class TimeZoneService extends Service
{
    protected HttpService $http;

    public function __construct(HttpService $http)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->http = $http;
    }

    public function getTimeZoneForCoordinates($latitude, $longitude)
    {
        // Cast so the setting also works as an array, e.g. an
        // override_timezone_default[timeZoneId]=America/New_York query param.
        return $this->settings->has("timezone_default")
            ? (object) $this->settings->get("timezone_default")
            : json_decode($this->http->get(sprintf(
                "%s&location=%s,%s&timestamp=%d",
                $this->settings->timezoneApiUri(),
                $latitude,
                $longitude,
                time() - (time() % 1800)
            ), 3600));
    }
}
