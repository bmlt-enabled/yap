<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Services\VolunteerService;
use App\Structures\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    protected VolunteerService $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
    }

    public function index(Request $request)
    {
        return response()
            ->json($this->volunteerService->getGroupsForServiceBody(
                $request->get("service_body_id"),
                $request->get("manage")
            ))
            ->header("Content-Type", "application/json");
    }

    public function store(Request $request)
    {
        $decodedData = json_decode($request->getContent());
        $groupData = new Group($decodedData);

        ConfigData::createGroup(
            $request->get("service_body_id"),
            $groupData
        );

        return self::index($request);
    }
}
