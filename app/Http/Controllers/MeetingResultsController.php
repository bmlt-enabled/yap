<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MeetingResultsController extends Controller
{
    public function index($latitude, $longitude): \Illuminate\Http\Response
    {
        if (is_numeric($latitude) && is_numeric($longitude)) {
            require_once __DIR__ . '/../../../legacy/_includes/functions.php';
            return response()->view(
                'meetingResults',
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'rootServerUrl' => getBMLTRootServer()
                ],
                200,
                ['contentType' => 'text/html']
            );
        } else {
            return response("404", 404);
        }
    }
}
