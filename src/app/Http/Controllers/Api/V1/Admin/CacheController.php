<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cache;
use App\Models\CacheRecordsConferenceParticipants;

class CacheController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/v1/cache",
     * operationId="Cache",
     * tags={"Cache"},
     * summary="Clear Cache",
     * description="Clear Cache",
     *      @OA\Response(
     *          response=200,
     *          description="Data Returned",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function store()
    {
        Cache::truncate();
        CacheRecordsConferenceParticipants::truncate();

        return response()->json(["status"=>"ok"]);
    }
}
