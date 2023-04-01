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
            $request->query("service_body_id"),
            $request->query("date_range_start"),
            $request->query("date_range_end"),
            $request->query("recurse")
        );

        return response()->json($data)
            ->header("Content-Type", "application/json");
    }
}
