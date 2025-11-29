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

    public function version()
    {
        $currentVersion = $this->upgradeAdvisorService->settings()->version();
        $versionInfo = $this->getVersionInfo($currentVersion);

        return response(json_encode($versionInfo))
            ->header("Content-Type", "application/json");
    }

    private function getVersionInfo($currentVersion)
    {
        try {
            // Fetch latest release from GitHub (excluding pre-releases)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/bmlt-enabled/yap/releases/latest');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Yap-Version-Check');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $release = json_decode($response, true);
                $latestVersion = ltrim($release['tag_name'] ?? '', 'v');

                // Compare versions
                $comparison = version_compare($currentVersion, $latestVersion);

                $versionInfo = [
                    'version' => $currentVersion,
                    'latest_version' => $latestVersion,
                ];

                if ($comparison > 0) {
                    // Current version is ahead - pre-release
                    $versionInfo['status'] = 'pre-release';
                    $versionInfo['message'] = "You are running a pre-release version ({$currentVersion}). Latest stable release is {$latestVersion}.";
                } elseif ($comparison < 0) {
                    // Current version is behind - update available
                    $versionInfo['status'] = 'update-available';
                    $versionInfo['message'] = "A new version ({$latestVersion}) is available. You are running {$currentVersion}.";
                } else {
                    // Current version matches latest
                    $versionInfo['status'] = 'current';
                    $versionInfo['message'] = "You are running the latest version.";
                }

                return $versionInfo;
            }
        } catch (Exception $e) {
            // If GitHub API fails, just return current version
        }

        // Fallback if GitHub API fails
        return [
            'version' => $currentVersion,
            'status' => 'unknown',
            'message' => 'Unable to check for updates.'
        ];
    }
}
