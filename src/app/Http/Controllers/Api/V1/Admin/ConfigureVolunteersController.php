<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;

/**
 * @OA\Tag(
 *     name="Volunteers",
 *     description="Volunteer configuration management endpoints"
 * )
 */
class ConfigureVolunteersController extends Controller
{
    protected ConfigData $configData;
    protected AuthorizationService $authz;

    public function __construct(ConfigData $configData, AuthorizationService $authz)
    {
        $this->configData = $configData;
        $this->authz = $authz;
    }

    private function authorizeServiceBody($serviceBodyId): bool
    {
        if ($this->authz->isTopLevelAdmin()) {
            return true;
        }
        $rights = session()->get('auth_service_bodies_rights') ?? [];
        return in_array($serviceBodyId, $rights);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/volunteers",
     *     tags={"Volunteers"},
     *     summary="Get volunteers configuration",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="query",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Volunteers configuration retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="service_body_id", type="integer"),
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $serviceBodyId = $request->get("serviceBodyId");

        // Validate serviceBodyId is numeric
        if (!is_numeric($serviceBodyId) || intval($serviceBodyId) <= 0) {
            return response()->json(['message' => 'Invalid service body ID'], 400);
        }
        $serviceBodyId = intval($serviceBodyId);

        // Check authorization
        if (!$this->authorizeServiceBody($serviceBodyId)) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $data = ConfigData::getVolunteers($serviceBodyId);

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
     *     path="/api/v1/volunteers",
     *     tags={"Volunteers"},
     *     summary="Store or update volunteers configuration",
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
     *             @OA\Property(
     *                 property="volunteers",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="number", type="string"),
     *                     @OA\Property(property="gender", type="string"),
     *                     @OA\Property(property="responder", type="boolean"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="language", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="notes", type="string"),
     *                     @OA\Property(property="shift_info", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Volunteers configuration saved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="service_body_id", type="integer"),
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $serviceBodyId = $request->get('serviceBodyId');

        // Validate serviceBodyId is numeric
        if (!is_numeric($serviceBodyId) || intval($serviceBodyId) <= 0) {
            return response()->json(['message' => 'Invalid service body ID'], 400);
        }
        $serviceBodyId = intval($serviceBodyId);

        // Check authorization
        if (!$this->authorizeServiceBody($serviceBodyId)) {
            return response()->json(['message' => 'Not authorized'], 403);
        }

        $volunteers = json_decode($request->getContent());

        $existingRecord = ConfigData::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_VOLUNTEERS_V2)
            ->first();

        if ($existingRecord) {
            ConfigData::updateVolunteers($serviceBodyId, $volunteers);
        } else {
            ConfigData::createVolunteers($serviceBodyId, $volunteers);
        }

        return self::index($request);
    }
}
