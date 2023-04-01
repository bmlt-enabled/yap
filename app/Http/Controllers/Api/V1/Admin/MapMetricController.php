<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use App\Constants\EventId;
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
        if ($request->query('format') == "csv") {
            $eventId = $request->query("event_id");
            $data = $this->reportsService->getMapMetricsCsv(
                $request->query("service_body_id"),
                $eventId,
                $request->query("date_range_start"),
                $request->query("date_range_end"),
                $request->query("recurse")
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
                $request->query("service_body_id"),
                $request->query("date_range_start"),
                $request->query("date_range_end"),
                $request->query("recurse")
            );

            return response()->json($data)
                ->header("Content-Type", "application/json");
        }
    }
}
