<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\AuthMechanism;
use App\Http\Controllers\Controller;
use App\Services\RootServerService;
use App\Utilities\Sort;
use Illuminate\Http\Request;

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
        return response()->json($this->rootServerService->getServiceBodies());
    }

}
