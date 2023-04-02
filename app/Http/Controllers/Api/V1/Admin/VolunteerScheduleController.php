<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\NoVolunteersException;
use App\Http\Controllers\Controller;
use App\Services\VolunteerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VolunteerScheduleController extends Controller
{
    protected VolunteerService $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
    }

    public function index(Request $request) : JsonResponse
    {
        try {
            $data = $this->volunteerService->getHelplineSchedule($request->query("service_body_id"));
        } catch (NoVolunteersException) {
            $data = [];
        }

        return response()->json($data)->header("Content-Type", "application/json");
    }
}
