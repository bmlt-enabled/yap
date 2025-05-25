<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Models\ConfigData;
use App\Constants\DataType;
use App\Structures\Settings;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Settings",
 *     description="System settings management endpoints"
 * )
 */
class SettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/settings/allowlist",
     *     tags={"Settings"},
     *     summary="Get allowlist settings",
     *     @OA\Response(
     *         response=200,
     *         description="Allowlist settings retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="setting", type="string"),
     *                 @OA\Property(property="default", type="string"),
     *                 @OA\Property(property="description", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function allowlist()
    {
        $allowlist = [];
        foreach ($this->settingsService->allowlist() as $key => $value) {
            if (!$value['hidden']) {
                $allowlist[] = [
                    "setting" => $key,
                    "default" => $value['default'],
                    "description" => !empty($value['description']) ? sprintf("%s%s", $this->settingsService->get("docs_base"), $value['description']) : ""
                ];
            }
        }
        return response()->json($allowlist);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/settings/serviceBody/{serviceBodyId}",
     *     tags={"Settings"},
     *     summary="Store or update service body configuration",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="path",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="fields",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="setting", type="string"),
     *                     @OA\Property(property="value", type="string"),
     *                     @OA\Property(property="default", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service body configuration saved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="languageSelections",
     *                 type="array",
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="settings",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="default", type="string"),
     *                     @OA\Property(property="docs", type="string"),
     *                     @OA\Property(property="key", type="string"),
     *                     @OA\Property(property="source", type="string"),
     *                     @OA\Property(property="value", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function saveServiceBodyConfiguration(Request $request)
    {
        $decodedDate = json_decode($request->getContent());
        $config = new Settings($decodedDate);
        $serviceBodyId = $request->route('serviceBodyId');

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

    /**
     * @OA\Get(
     *     path="/api/v1/settings",
     *     tags={"Settings"},
     *     summary="Get system settings",
     *     @OA\Response(
     *         response=200,
     *         description="Settings retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="languageSelections",
     *                 type="array",
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="settings",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="default", type="string"),
     *                     @OA\Property(property="docs", type="string"),
     *                     @OA\Property(property="key", type="string"),
     *                     @OA\Property(property="source", type="string"),
     *                     @OA\Property(property="value", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $settings = [];

        foreach ($this->settingsService->allowlist() as $key => $value) {
            if (!$value['hidden']) {
                $settings[] = [
                    "default" => $value['default'],
                    "docs" => !empty($value['description']) ? sprintf("%s%s", $this->settingsService->get("docs_base"), $value['description']) : "",
                    "key" => $key,
                    "source" => $this->settingsService->source($key),
                    "value" => $this->settingsService->get($key),
                ];
            }
        }

        return response()->json([
            'languageSelections'=>$this->settingsService->languageSelections(),
            'settings'=>$settings,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/settings/serviceBody/{serviceBodyId}",
     *     tags={"Settings"},
     *     summary="Get service body configuration",
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         in="path",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service body configuration retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="setting", type="string"),
     *                 @OA\Property(property="value", type="string"),
     *                 @OA\Property(property="default", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function getServiceBodyConfiguration(Request $request)
    {
        $serviceBodyId = $request->route('serviceBodyId');
        $config = ConfigData::where('service_body_id', $serviceBodyId)
            ->where('data_type', DataType::YAP_CONFIG_V2)
            ->first();

        if (!$config) {
            return response()->json([]);
        }

        $decodedData = json_decode($config->data);
        if (!isset($decodedData[0]->fields)) {
            return response()->json([]);
        }

        $fields = [];
        foreach ($decodedData[0]->fields as $field) {
            $fields[] = [
                'setting' => $field->setting,
                'value' => $field->value,
            ];
        }

        return response()->json($fields);
    }
}
