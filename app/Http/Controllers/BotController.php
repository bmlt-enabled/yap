<?php

namespace App\Http\Controllers;

use App\Services\MeetingResultsService;
use Illuminate\Http\Request;

class BotController extends Controller
{
    private MeetingResultsService $results;

    public function __construct(MeetingResultsService $results)
    {
        $this->results = $results;
    }

    public function getMeetings(Request $request)
    {
        $meetings = $this->results->getMeetings(
            $request->has('latitude') ? $request->get('latitude') : null,
            $request->has('longitude') ? $request->get('longitude') : null,
            $request->has('results_count') ? $request->get('results_count') : 5,
            $request->has('today') ? $request->get('today') : null,
            $request->has('tomorrow') ? $request->get('tomorrow') : null
        );
        return response()->json($meetings)->header("Content-Type", "application/json");
    }

    public function getServiceBodyCoverage(Request $request)
    {
        $coverage = $this->results->getServiceBodyCoverage(
            $request->has('latitude') ? $request->get('latitude') : null,
            $request->has('longitude') ? $request->get('longitude') : null
        );
        return response()->json($coverage)->header("Content-Type", "application/json");
    }
}
