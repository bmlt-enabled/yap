<?php
namespace App\Repositories;

use App\Constants\DataType;
use App\Services\SettingsService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GeocodingRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(App::make(SettingsService::class));
    }

    public function ping($address)
    {
        return json_decode(Http::get($this->settings->geocodingApiUri()
            . "&address="
            . urlencode($address)));
    }

    public function getInfo($address)
    {
        return Http::get($this->settings->geocodingApiUri()
            . "&address="
            . urlencode($address)
            . "&components=" . urlencode($this->settings->get('location_lookup_bias')))->body();
    }
}
