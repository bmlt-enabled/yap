<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Response;

class MeetingResultsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index($latitude, $longitude): Response
    {
        if (is_numeric($latitude) && is_numeric($longitude)) {
            return response()->view(
                'meetingResults',
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'rootServerUrl' => $this->settingsService->getBMLTRootServer()
                ],
                200,
                ['contentType' => 'text/html']
            );
        } else {
            return response("404", 404);
        }
    }
}
