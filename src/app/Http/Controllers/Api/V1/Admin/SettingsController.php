<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;

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
}
