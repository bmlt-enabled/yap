<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Services\VolunteerService;
use App\Structures\Group;
use Illuminate\Http\Request;
use stdClass;

/**
 * @OA\Tag(
 *     name="Groups",
 *     description="Group management endpoints"
 * )
 */
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
                'service_body_id' => (int)$item->service_body_id,
                'id' => (int)$item->id,
                'parent_id' => null,
                'data' => json_decode($item->data)
            ];
        }

        return $results;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/groups",
     *     tags={"Groups"},
     *     summary="Get groups for a service body",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="query",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="manage",
     *         in="query",
     *         required=false,
     *         description="Whether to include management data",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Groups retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="service_body_id", type="integer"),
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true),
     *                 @OA\Property(property="data", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $groupsData = $this
            ->getGroupsData(intval($request->get("serviceBodyId")), boolval($request->get("manage") ?? false));
        return response()->json($groupsData)->header("Content-Type", "application/json");
    }

    /**
     * @OA\Put(
     *     path="/api/v1/groups/{id}",
     *     tags={"Groups"},
     *     summary="Update a group",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="enabled", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group updated successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="service_body_id", type="integer"),
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true),
     *                 @OA\Property(property="data", type="object")
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/groups",
     *     tags={"Groups"},
     *     summary="Create a new group",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="query",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="enabled", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group created successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="service_body_id", type="integer"),
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true),
     *                 @OA\Property(property="data", type="object")
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/groups/{id}",
     *     tags={"Groups"},
     *     summary="Delete a group",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the group to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Group not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $response = ConfigData::deleteGroup($id);
        if ($response === 1) {
            return response()->json(['message' => sprintf('Group %s deleted successfully', $id)]);
        }

        return response()->json(['message' => 'Not found'], 404);
    }
}
