<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cache;
use App\Models\CacheRecordsConferenceParticipants;

class CacheController extends Controller
{
    public function store()
    {
        Cache::truncate();
        CacheRecordsConferenceParticipants::truncate();

        return response()->json(["status"=>"ok"]);
    }
}
