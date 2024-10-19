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
                $request->get("serviceBodyId"),
                $request->get("manage")
            ))
            ->header("Content-Type", "application/json");
    }

    public function update(Request $request, $id)
    {
        $decodedData = json_decode($request->getContent());
        $groupData = new Group($decodedData);

        $group = ConfigData::updateGroup(
            $id,
            $groupData
        );

        $groups = $this->volunteerService->getGroupsForServiceBody(
            $group->service_body_id,
        );

        return response()
            ->json($groups)
            ->header("Content-Type", "application/json");
    }

    public function store(Request $request)
    {
        $decodedData = json_decode($request->getContent());
        $groupData = new Group($decodedData);

        ConfigData::createGroup(
            $request->get('serviceBodyId'),
            $groupData
        );

        return self::index($request);
    }

    public function destroy($id)
    {
        $response = ConfigData::deleteGroup($id);
        if ($response === 1) {
            return response()->json(['message' => sprintf('Group %s deleted successfully', $id)]);
        }

        return response()->json(['message' => 'Not found'], 404);
    }
}
