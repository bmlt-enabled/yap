<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\EventId;
use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="MapMetrics",
 *     description="Map metrics and reporting endpoints"
 * )
 */
class MapMetricController extends Controller
{
    private $reportsService;

    public function __construct(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/mapmetrics",
     *     tags={"MapMetrics"},
     *     summary="Get map metrics report",
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
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         required=false,
     *         description="Output format (csv or json)",
     *         @OA\Schema(type="string", enum={"csv", "json"})
     *     ),
     *     @OA\Parameter(
     *         name="event_id",
     *         in="query",
     *         required=false,
     *         description="Event ID for filtering metrics",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Map metrics report retrieved successfully",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="total_calls", type="integer"),
     *                 @OA\Property(property="total_duration", type="integer"),
     *                 @OA\Property(property="average_duration", type="number", format="float"),
     *                 @OA\Property(property="calls_by_location", type="object"),
     *                 @OA\Property(property="calls_by_region", type="object")
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="text/plain",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        if ($request->get('format') == "csv") {
            $eventId = $request->get("event_id");
            $data = $this->reportsService->getMapMetricsCsv(
                $request->get("service_body_id"),
                $eventId,
                $request->get("date_range_start"),
                $request->get("date_range_end"),
                filter_var($request->get("recurse"), FILTER_VALIDATE_BOOLEAN)
            );

            return response($data)
                ->header("Content-Type", "text/plain")
                ->header("Content-Length", strlen($data))
                ->header("Content-Disposition", sprintf(
                    'attachment; filename="%s-map-metrics.csv"',
                    $eventId == EventId::VOLUNTEER_SEARCH ? "volunteers" : "meetings"
                ));
        } else {
            $data = $this->reportsService->getMapMetrics(
                $request->get("service_body_id"),
                $request->get("date_range_start"),
                $request->get("date_range_end"),
                filter_var($request->get("recurse"), FILTER_VALIDATE_BOOLEAN)
            );

            return response()->json($data)
                ->header("Content-Type", "application/json");
        }
    }
}
