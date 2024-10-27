<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Services\VolunteerService;
use Illuminate\Http\Request;
use stdClass;

class GroupVolunteerController extends Controller
{
    protected VolunteerService $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
    }

    public function index(Request $request)
    {
        $data = ConfigData::getGroupVolunteers($request->get("groupId"));

        if (count($data) > 0) {
            return response()->json([
                'service_body_id' => $data[0]->service_body_id,
                'id' => $data[0]->id,
                'parent_id' => $data[0]->parent_id ?? null,
                'data' => json_decode($data[0]->data)
            ])->header("Content-Type", "application/json");
        } else {
            return response()->json(new stdClass())->header("Content-Type", "application/json");
        }
    }

    public function store(Request $request)
    {
        $volunteers = json_decode($request->getContent());
        $groupId = $request->get('groupId');
        $serviceBodyId = $request->get('serviceBodyId') ?? null;

        $existingRecord = ConfigData::where('parent_id', $groupId)
            ->where('data_type', DataType::YAP_GROUP_VOLUNTEERS_V2)
            ->first();

        if ($existingRecord) {
            // If the record exists, update it
            ConfigData::updateGroupVolunteers($groupId, $volunteers);
        } else {
            // Otherwise, create a new record
            ConfigData::createGroupVolunteers($serviceBodyId, $groupId, $volunteers);
        }

        return self::index($request);
    }
}
