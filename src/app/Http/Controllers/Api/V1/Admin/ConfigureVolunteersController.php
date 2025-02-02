<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;

/**
 * @OA\Tag(
 *     name="ConfigureVolunteers",
 *     description="For configuring volunteers"
 * )
 */
class ConfigureVolunteersController extends Controller
{
    protected ConfigData $configData;

    public function __construct(ConfigData $configData)
    {
        $this->configData = $configData;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/volunteers",
     *     tags={"ConfigureVolunteers"},
     *     summary="Get volunteers configuration",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="query",
     *         required=true,
     *         description="Service body ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
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
        $data = ConfigData::getVolunteers($request->get("serviceBodyId"));

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

    /**
     * @OA\Post(
     *     path="/api/v1/volunteers",
     *     tags={"ConfigureVolunteers"},
     *     summary="Store or update volunteers configuration",\
     *     @OA\Parameter(
     *          name="serviceBodyId",
     *          in="query",
     *          required=true,
     *          description="Service body ID",
     *          @OA\Schema(type="integer")
     *      ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="volunteers", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $volunteers = json_decode($request->getContent());
        $serviceBodyId = $request->get('serviceBodyId');

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
