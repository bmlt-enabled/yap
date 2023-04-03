<?php

namespace App\Services;

use App\Models\Coordinates;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public SettingsService $settings;
    private string $googleMapsEndpoint;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
        $this->googleMapsEndpoint = sprintf(
            "https://maps.googleapis.com/maps/api/geocode/json?key=%s",
            $this->settings->get("google_maps_api_key")
        );
    }

    public function getCoordinatesForAddress($address)
    {
        $coordinates = new Coordinates();

        if (strlen($address) > 0) {
            foreach ($this->settings->get("custom_geocoding") as $item) {
                $arr_item = (array) $item;
                if ($arr_item["location"] == $address) {
                    $coordinates->location = $arr_item["location"];
                    $coordinates->latitude = $arr_item["latitude"];
                    $coordinates->longitude = $arr_item["longitude"];

                    return $coordinates;
                }
            }

            if (isset($_REQUEST['stub_google_maps_endpoint']) && $_REQUEST['stub_google_maps_endpoint']) {
                $map_details_response = json_encode(StubService::geocode());
            } else {
                $map_details_response = Http::get($this->googleMapsEndpoint
                    . "&address="
                    . urlencode($address)
                    . "&components=" . urlencode($this->settings->get('location_lookup_bias')));
            }

            $map_details = json_decode($map_details_response);

            if (count($map_details->results) > 0) {
                $coordinates->location = $map_details->results[0]->formatted_address;
                $geometry = $map_details->results[0]->geometry->location;
                $coordinates->latitude = $geometry->lat;
                $coordinates->longitude = $geometry->lng;
            }
        }

        return $coordinates;
    }
}
