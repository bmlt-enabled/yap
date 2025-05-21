<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

/**
 * @OA\Tag(
 *     name="Session",
 *     description="Session management endpoints"
 * )
 */
class SessionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/session",
     *     tags={"Session"},
     *     summary="Clear session data",
     *     description="Clears all cache, override, and call state data from the session",
     *     @OA\Response(
     *         response=200,
     *         description="Session cleared successfully",
     *         @OA\MediaType(
     *             mediaType="text/plain",
     *             @OA\Schema(type="string", example="OK")
     *         )
     *     )
     * )
     */
    public function store()
    {
        foreach (Session::all() as $key => $value) {
            if (str_starts_with($key, "cache_") || str_starts_with($key, "override_") || str_starts_with($key, "call_state")) {
                Session::forget($key);
            }
        }

        return response("OK")
            ->header('Content-Type', 'text/plain');
    }
}
