<?php

namespace App\Http\Controllers;

use App\Services\UpgradeService;
use Exception;
use Illuminate\Http\Request;

class UpgradeAdvisorController extends Controller
{
    protected UpgradeService $upgradeAdvisorService;

    public function __construct(UpgradeService $upgradeAdvisorService)
    {
        $this->upgradeAdvisorService = $upgradeAdvisorService;
    }

    public function index(Request $request)
    {
        if (!file_exists('config.php') && $request->get('status-check')) {
            return response(json_encode(["status"=>false,"message"=>"Waiting for config.php to exist..."]))
                ->header("Content-Type", "application/json");
        }
        try {
            return response(json_encode($this->upgradeAdvisorService->getStatus()))
                ->header("Content-Type", "application/json");
        } catch (Exception $e) {
            return response(json_encode(["status"=>false,"message"=>sprintf("Error: %s", $e->getMessage())]))
                ->header("Content-Type", "application/json");
        }
    }
}
