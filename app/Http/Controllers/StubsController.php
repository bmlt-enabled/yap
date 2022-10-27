<?php
namespace App\Http\Controllers;

use App\Services\StubService;

class StubsController extends Controller
{
    public function timezone()
    {
        return StubService::timezone();
    }

    public function geocode()
    {
        return StubService::geocode();
    }
}
