<?php

namespace App\Simulator\Scenarios;

/**
 * The knobs a scenario run accepts, filled from yap:simulate's options.
 */
class ScenarioOptions
{
    public function __construct(
        public string $from = '+17325551212',
        public string $to = '+12125551212',
        // Where the caller says they are. By default the scenarios pin it to
        // coordinates and a time zone with override_custom_geocoding and
        // override_timezone_default, so no Google Maps key is needed;
        // --real-geocoding sends it to Google's geocoding and time zone APIs.
        public string $location = '14456',
        public string $latitude = '42.867970',
        public string $longitude = '-76.985573',
        public string $timezone = 'America/New_York',
        public bool $realGeocoding = false,
        public ?string $serviceBodyId = null,
        public ?string $volunteer = null,
        public string $outcome = 'answer',
        public bool $seed = false,
    ) {
    }

    /**
     * Query string that pins the scenario's location without Google. Every
     * request runs CallSession, which copies override_* params into the call's
     * session, so they only need to ride on the first webhook.
     *
     * @return array<string, mixed>
     */
    public function locationOverrides(): array
    {
        if ($this->realGeocoding) {
            return [];
        }

        return [
            'override_custom_geocoding' => [[
                'location' => $this->location,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]],
            'override_timezone_default' => ['timeZoneId' => $this->timezone],
        ];
    }
}
