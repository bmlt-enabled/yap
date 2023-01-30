<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use UpgradeAdvisor;

class UpgradeAdvisorController extends Controller
{
    public function index(Request $request)
    {
        if (!file_exists('config.php') && $request->query('status-check')) {
            return response(json_encode(["status"=>false,"message"=>"Waiting for config.php to exist..."]))
                ->header("Content-Type", "application/json");
        }
        try {
            require_once __DIR__ . '/../../../legacy/_includes/functions.php';
            return response(json_encode(UpgradeAdvisor::getStatus()))
                ->header("Content-Type", "application/json");
        } catch (Exception $e) {
            return response(json_encode(["status"=>false,"message"=>sprintf("Error: %s", $e->getMessage())]))
                ->header("Content-Type", "application/json");
        }
    }
}
