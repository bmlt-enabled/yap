<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Metrics",
 *     description="Metrics and reporting endpoints"
 * )
 */
class MetricController extends Controller
{
    private ReportsService $reportsService;

    public function __construct(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/metrics",
     *     tags={"Metrics"},
     *     summary="Get metrics report",
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
     *         description="Whether to include metrics from child service bodies",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Metrics report retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_calls", type="integer"),
     *             @OA\Property(property="total_duration", type="integer"),
     *             @OA\Property(property="average_duration", type="number", format="float"),
     *             @OA\Property(property="calls_by_hour", type="object"),
     *             @OA\Property(property="calls_by_day", type="object"),
     *             @OA\Property(property="calls_by_volunteer", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->reportsService->getMetrics(
            $request->get("service_body_id"),
            $request->get("date_range_start"),
            $request->get("date_range_end"),
            filter_var($request->get("recurse"), FILTER_VALIDATE_BOOLEAN)
        );

        return response()->json($data)
            ->header("Content-Type", "application/json");
    }
}
