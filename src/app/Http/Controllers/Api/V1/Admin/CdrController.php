<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthorizationService;
use App\Services\ReportsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CdrController extends Controller
{
    private ReportsService $reportsService;
    private AuthorizationService $authorizationService;

    public function __construct(ReportsService $reportsService, AuthorizationService $authorizationService)
    {
        $this->reportsService = $reportsService;
        $this->authorizationService = $authorizationService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/cdr",
     *     tags={"Reports"},
     *     summary="Get Call Detail Records report",
     *     @OA\Parameter(
     *         name="service_body_id",
     *         in="query",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_range_start",
     *         in="query",
     *         required=true,
     *         description="Start date for the report range",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_range_end",
     *         in="query",
     *         required=true,
     *         description="End date for the report range",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="recurse",
     *         in="query",
     *         required=false,
     *         description="Whether to include records from child service bodies",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call Detail Records retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="call_sid", type="string"),
     *                 @OA\Property(property="from", type="string"),
     *                 @OA\Property(property="to", type="string"),
     *                 @OA\Property(property="start_time", type="string", format="date-time"),
     *                 @OA\Property(property="end_time", type="string", format="date-time"),
     *                 @OA\Property(property="duration", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="service_body_id", type="integer"),
     *                 @OA\Property(property="volunteer_id", type="integer", nullable=true),
     *                 @OA\Property(property="volunteer_name", type="string", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $serviceBodyId = $request->get('service_body_id');

        // Validate access to service_body_id=0 (unattached records)
        if (intval($serviceBodyId) == 0 && !$this->authorizationService->isRootServiceBodyAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($this->reportsService->getCallDetailRecords(
            $serviceBodyId,
            $request->get('date_range_start'),
            $request->get('date_range_end'),
            filter_var($request->get('recurse'), FILTER_VALIDATE_BOOLEAN)
        ))->header("Content-Type", "application/json");
    }
}
