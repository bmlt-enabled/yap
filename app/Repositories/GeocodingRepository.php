<?php
namespace App\Repositories;

use App\Constants\DataType;
use App\Services\SettingsService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GeocodingRepository extends Repository
{
    private string $googleMapsEndpoint;

    public function __construct()
    {
        parent::__construct(App::make(SettingsService::class));
        $this->googleMapsEndpoint = sprintf(
            "https://maps.googleapis.coma/maps/api/geocode/json?key=%s",
            $this->settings->get("google_maps_api_key")
        );
    }

    public function ping($address)
    {
        return json_decode(Http::get($this->googleMapsEndpoint
            . "&address="
            . urlencode($address)));
    }

    public function getInfo($address)
    {
        return Http::get($this->googleMapsEndpoint
            . "&address="
            . urlencode($address)
            . "&components=" . urlencode($this->settings->get('location_lookup_bias')))->json();
    }
}
