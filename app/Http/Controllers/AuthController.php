<?php
namespace App\Http\Controllers;

use App\Constants\AuthMechanism;
use App\Constants\Http;

class AuthController extends Controller
{
    public function logout($auth = true) {
        if (isset($_SESSION['auth_mechanism']) && $_SESSION['auth_mechanism'] == AuthMechanism::V1) {
            if (isset($_SESSION['bmlt_auth_session']) && $_SESSION['bmlt_auth_session'] != null) {
                require_once __DIR__ . '/../../../legacy/_includes/functions.php';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, sprintf('%s/local_server/server_admin/xml.php?admin_action=logout', getAdminBMLTRootServer()));
                curl_setopt($ch, CURLOPT_USERAGENT, Http::UserAgent);
                curl_setopt($ch, CURLOPT_COOKIE, getBMLTAuthSessionCookies());
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

        return redirect(!$auth ? "/admin?auth=false" : "/admin");
    }

    public function invalid()
    {
        return AuthController::logout(false);
    }

    function getBMLTAuthSessionCookies()
    {
        return isset($_SESSION['bmlt_auth_session']) ? implode(";", $_SESSION['bmlt_auth_session']) : "";
    }
}
