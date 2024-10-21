<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Services\VolunteerService;
use App\Structures\Group;
use Illuminate\Http\Request;
use stdClass;

class GroupController extends Controller
{
    protected VolunteerService $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
    }

    private function getGroupsData(int $serviceBodyId, bool $manage = false)
    {
        $data = $this->volunteerService->getGroupsForServiceBody(
            $serviceBodyId,
            $manage
        );

        $results = [];

        foreach ($data as $item) {
            $results[] = [
                'service_body_id' => $item->service_body_id,
                'id' => $item->id,
                'parent_id' => NULL,
                'data' => json_decode($item->data)
            ];
        }

        return $results;
    }

    public function index(Request $request)
    {
        $groupsData = $this
            ->getGroupsData(intval($request->get("serviceBodyId")), boolval($request->get("manage") ?? false));
        return response()->json($groupsData)->header("Content-Type", "application/json");
    }

    public function update(Request $request, $id)
    {
        $decodedData = json_decode($request->getContent());
        $groupData = new Group($decodedData);

        $group = ConfigData::updateGroup(
            $id,
            $groupData
        );

        $groupsData = $this->getGroupsData(intval($group->service_body_id));
        return response()->json($groupsData)->header("Content-Type", "application/json");
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
