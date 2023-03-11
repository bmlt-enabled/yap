<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\AuthMechanism;
use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Repositories\ConfigRepository;
use App\Services\RootServerService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RootServerServiceBodiesController extends Controller
{
    protected RootServerService $rootServerService;

    public function __construct(RootServerService $rootServerService)
    {
        $this->rootServerService = $rootServerService;
    }

    /**
     * @OA\Get(
     * path="/api/v1/rootServer/servicebodies",
     * operationId="RootServer",
     * tags={"RootServer"},
     * summary="Get ServiceBodies",
     * description="Get ServiceBodies",
     *      @OA\Response(
     *          response=200,
     *          description="Data Returned",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function index(Request $request)
    {
        $_SESSION['auth_mechanism'] = AuthMechanism::V2;
        $_SESSION['auth_is_admin'] = true;
        return response()->json($this->rootServerService->getServiceBodiesForUser());
    }
}
