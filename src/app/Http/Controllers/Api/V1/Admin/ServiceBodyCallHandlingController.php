<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Structures\ServiceBodyCallHandling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;

class ServiceBodyCallHandlingController extends Controller
{
    protected ConfigData $configData;

    public function __construct(ConfigData $configData)
    {
        $this->configData = $configData;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/callHandling",
     *     tags={"CallHandling"},
     *     summary="Get service body call handling configuration",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="query",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call handling configuration retrieved successfully",
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
        $data = ConfigData::getCallHandling($request->get("serviceBodyId"));

        if (count($data) > 0) {
            return response()->json([
                'service_body_id' => $data[0]->service_body_id,
                'id' => $data[0]->id,
                'parent_id' => null,
                'data' => json_decode($data[0]->data)
            ])->header("Content-Type", "application/json");
        } else {
            return response()->json(new stdClass())->header("Content-Type", "application/json");
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/callHandling",
     *     tags={"CallHandling"},
     *     summary="Store or update service body call handling configuration",
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
     *             @OA\Property(property="enabled", type="boolean"),
     *             @OA\Property(property="volunteer_search_enabled", type="boolean"),
     *             @OA\Property(property="volunteer_search_radius", type="integer"),
     *             @OA\Property(property="volunteer_search_max_results", type="integer"),
     *             @OA\Property(property="volunteer_search_timeout", type="integer"),
     *             @OA\Property(property="volunteer_search_retry_count", type="integer"),
     *             @OA\Property(property="volunteer_search_retry_delay", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Call handling configuration saved successfully",
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
        $decodedData = json_decode($request->getContent());
        $serviceBodyCallHandling = new ServiceBodyCallHandling($decodedData);

        $serviceBodyId = $request->get('serviceBodyId');

        $existingRecord = ConfigData::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CALL_HANDLING_V2)
            ->first();

        if ($existingRecord) {
            // If the record exists, update it
            ConfigData::updateServiceBodyCallHandling($request->get('serviceBodyId'), $serviceBodyCallHandling);
        } else {
            // Otherwise, create a new record
            ConfigData::createServiceBodyCallHandling($request->get('serviceBodyId'), $serviceBodyCallHandling);
        }

        return self::index($request);
    }
}
