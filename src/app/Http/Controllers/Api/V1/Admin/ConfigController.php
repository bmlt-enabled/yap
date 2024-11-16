<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Structures\Settings;
use Illuminate\Http\Request;
use stdClass;

class ConfigController extends Controller
{
    public function __construct()
    {
    }

    /**
     * @OA\Get(
     * path="/api/v1/config",
     * operationId="Config",
     * tags={"Config"},
     * summary="Get Configuration",
     * description="Get Configuration",
     *      @OA\Parameter(
     *         description="The service body ID",
     *         in="query",
     *         name="service_body_id",
     *         required=true,
     *         @OA\Schema(type="number"),
     *      ),
     *      @OA\Parameter(
     *         description="The data type",
     *         in="query",
     *         name="data_type",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         @OA\Examples(example="config", value="_YAP_CONFIG_V2_", summary="Configuration"),
     *         @OA\Examples(example="volunteers", value="_YAP_VOLUNTEERS_V2_", summary="Volunteers"),
     *         @OA\Examples(example="groups", value="_YAP_GROUPS_V2_", summary="Groups"),
     *         @OA\Examples(example="callhandling", value="_YAP_CALL_HANDLING_V2_", summary="Call Handling"),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Data Returned",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function index(Request $request)
    {
        $data = ConfigData::getServiceBodyConfiguration($request->get("serviceBodyId"));

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
        $decodedDate = json_decode($request->getContent());
        $config = new Settings($decodedDate);
        $serviceBodyId = $request->get('serviceBodyId');

        $existingRecord = ConfigData::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CONFIG_V2)
            ->first();

        if ($existingRecord) {
            // If the record exists, update it
            ConfigData::updateServiceBodyConfiguration($serviceBodyId, $config);
        } else {
            // Otherwise, create a new record
            ConfigData::createServiceBodyConfiguration($serviceBodyId, $config);
        }

        return self::index($request);
    }
}
