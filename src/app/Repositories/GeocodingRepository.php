<?php
namespace App\Repositories;

use App\Services\HttpService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\App;

class GeocodingRepository extends Repository
{
    protected HttpService $http;

    public function __construct(HttpService $http)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->http = $http;
    }

    public function ping($address)
    {
        return json_decode($this->http->get($this->settings->geocodingApiUri()
            . "&address="
            . urlencode($address), 3600));
    }

    public function getInfo($address)
    {
        return $this->http->get($this->settings->geocodingApiUri()
            . "&address="
            . urlencode($address)
            . "&components=" . urlencode($this->settings->get('location_lookup_bias')), 3600);
    }
}
