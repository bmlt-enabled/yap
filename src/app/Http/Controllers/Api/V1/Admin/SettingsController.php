<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

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
