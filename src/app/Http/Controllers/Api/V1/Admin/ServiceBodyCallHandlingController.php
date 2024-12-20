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
