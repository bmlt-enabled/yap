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

    /**
     * @OA\Get(
     *     path="/api/v1/groups/volunteers",
     *     tags={"Groups"},
     *     summary="Get volunteers for a group",
     *     description="Retrieves volunteers assigned to a specific group",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="groupId",
     *         in="query",
     *         required=true,
     *         description="ID of the group",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group volunteers retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="service_body_id", type="integer"),
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true),
     *             @OA\Property(property="data", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $data = ConfigData::getGroupVolunteers($request->get("groupId"));

        if (count($data) > 0) {
            return response()->json([
                'service_body_id' => $data[0]->service_body_id,
                'id' => $data[0]->id,
                'parent_id' => $data[0]->parent_id ?? null,
                'data' => $data[0]->data
            ])->header("Content-Type", "application/json");
        } else {
            return response()->json(new stdClass())->header("Content-Type", "application/json");
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/groups/volunteers",
     *     tags={"Groups"},
     *     summary="Create or update volunteers for a group",
     *     description="Assigns or updates volunteers for a specific group",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="groupId",
     *         in="query",
     *         required=true,
     *         description="ID of the group",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="query",
     *         required=false,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of volunteer IDs to assign to the group",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group volunteers updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="service_body_id", type="integer"),
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true),
     *             @OA\Property(property="data", type="string")
     *         )
     *     )
     * )
     */
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
