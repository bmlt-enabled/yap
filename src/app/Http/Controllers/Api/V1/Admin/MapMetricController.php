<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\EventId;
use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\Request;

class MapMetricController extends Controller
{
    private $reportsService;

    public function __construct(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

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
