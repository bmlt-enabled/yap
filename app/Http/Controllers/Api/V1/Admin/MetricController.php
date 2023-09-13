<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\EventId;
use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricController extends Controller
{
    private ReportsService $reportsService;

    public function __construct(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->reportsService->getMetrics(
            $request->get("service_body_id"),
            $request->get("date_range_start"),
            $request->get("date_range_end"),
            $request->get("recurse")
        );

        return response()->json($data)
            ->header("Content-Type", "application/json");
    }
}
