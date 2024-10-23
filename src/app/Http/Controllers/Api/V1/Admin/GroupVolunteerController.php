<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\VolunteerService;
use Illuminate\Http\Request;

class GroupVolunteerController extends Controller
{
    protected VolunteerService $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
    }

    public function index(Request $request)
    {
        return response()
            ->json($this->volunteerService->getGroupVolunteers(
                $request->get("group_id"),
            ))
            ->header("Content-Type", "application/json");
    }
}
