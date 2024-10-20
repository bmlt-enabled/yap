<?php

namespace App\Http\Controllers\Api\V1\Admin;

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
                'parent_id' => $data[0]->parent_id ?? "null",
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

        ConfigData::createCallHandling(
            $request->get('serviceBodyId'),
            0,
            $serviceBodyCallHandling
        );

        return self::index($request);
    }
}
