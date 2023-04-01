<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CdrController extends Controller
{
    private $reportsService;

    public function __construct(ReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->reportsService->getCallDetailRecords(
            $request->query('service_body_id'),
            $request->query('date_range_start'),
            $request->query('date_range_end'),
            $request->query('recurse')
        ))->header("Content-Type", "application/json");
    }
}
