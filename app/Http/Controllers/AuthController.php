<?php
namespace App\Http\Controllers;

use App\Constants\AuthMechanism;
use App\Constants\Http;
use App\Services\AuthorizationService;
use App\Services\SettingsService;

class AuthController extends Controller
{
    protected AuthorizationService $permissions;
    protected SettingsService $settings;

    public function __construct(AuthorizationService $permissions, SettingsService $settings)
    {
        $this->permissions = $permissions;
        $this->settings = $settings;
    }

    public function logout($auth = true)
    {
        $this->clearSession();
        return redirect(!$auth ? "/admin?auth=false" : "/admin");
    }

    public function timeout()
    {
        $this->clearSession();
        return redirect("/admin?expired=true");
    }

    public function invalid()
    {
        return self::logout(false);
    }

    private function clearSession()
    {
        if (isset($_SESSION['auth_mechanism']) && $_SESSION['auth_mechanism'] == AuthMechanism::V1) {
            if (isset($_SESSION['bmlt_auth_session']) && $_SESSION['bmlt_auth_session'] != null) {
                require_once __DIR__ . '/../../../legacy/_includes/functions.php';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, sprintf('%s/local_server/server_admin/xml.php?admin_action=logout', $this->settings->getAdminBMLTRootServer()));
                curl_setopt($ch, CURLOPT_USERAGENT, Http::USERAGENT);
                curl_setopt($ch, CURLOPT_COOKIE, self::getBMLTAuthSessionCookies());
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $res = curl_exec($ch);
                curl_close($ch);
            } else {
                $res = "BYE;";
            }

            session_unset();
        } else {
            session_unset();
        }
    }

    public function rights()
    {
        $rights = $this->permissions->getServiceBodyRights();
        return $rights ?? response()->json(['error' => 'Unauthorized'], 403);
    }

    private function getBMLTAuthSessionCookies()
    {
        return isset($_SESSION['bmlt_auth_session']) ? implode(";", $_SESSION['bmlt_auth_session']) : "";
    }

}
