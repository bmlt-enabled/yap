<?php

namespace app\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;

class ConfigureVolunteersController extends Controller
{
    protected ConfigData $configData;

    public function __construct(ConfigData $configData)
    {
        $this->configData = $configData;
    }

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

    public function store(Request $request)
    {
        $volunteers = json_decode($request->getContent());
        $serviceBodyId = $request->get('serviceBodyId');

        $existingRecord = ConfigData::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_VOLUNTEERS_V2)
            ->first();

        if ($existingRecord) {
            // If the record exists, update it
            ConfigData::updateVolunteers($serviceBodyId, $volunteers);
        } else {
            // Otherwise, create a new record
            ConfigData::createVolunteers($serviceBodyId, $volunteers);
        }

        return self::index($request);
    }
}
