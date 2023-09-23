<?php

namespace App\Services;

use App\Models\Coordinates;
use App\Repositories\GeocodingRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

class GeocodingService extends Service
{
    protected GeocodingRepository $geocodingRepository;

    public function __construct(GeocodingRepository $geocodingRepository)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->geocodingRepository = $geocodingRepository;
    }

    public function getCoordinatesForAddress($address): Coordinates
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

            $map_details = json_decode($this->geocodingRepository->getInfo($address));

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
