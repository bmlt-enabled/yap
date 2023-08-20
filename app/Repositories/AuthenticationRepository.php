<?php

namespace App\Repositories;

use App\Services\HttpService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;

class AuthenticationRepository
{
    protected SettingsService $settings;
    protected HttpService $http;

    public function __construct(SettingsService $settings, HttpService $http)
    {
        $this->settings = $settings;
        $this->http = $http;
    }

    public function authV1($username, $password): bool
    {
        $endpoint = ($this->settings->has('alt_auth_method') && $this->settings->get('alt_auth_method') ? '/index.php' : '/local_server/server_admin/xml.php');
        $res = $this->http->post(sprintf("%s%s", $this->settings->getAdminBMLTRootServer(), $endpoint), "admin_action=login&c_comdef_admin_login='.$username.'&c_comdef_admin_password='.urlencode($password)");
        $is_authed = preg_match('/^OK$/', str_replace(array("\r", "\n"), '', $res->body())) == 1;
        $_SESSION["bmlt_auth_session"] = $is_authed ? $this->getCookiesFromHeaders() : null;
        return $is_authed;
    }

    public function verifyV1(): bool
    {
        $endpoint = '/local_server/server_admin/xml.php?admin_action=get_permissions';
        $res = $this->http->getWithAuth(sprintf("%s%s", $this->settings->getAdminBMLTRootServer(), $endpoint));
        if ($res == null) {
            return false;
        }

        return !str_contains($res, 'NOT AUTHORIZED');
    }

    public function logoutV1(): void
    {
        $endpoint = 'local_server/server_admin/xml.php?admin_action=logout';
        $this->http->getWithAuth(sprintf("%s%s", $this->settings->getAdminBMLTRootServer(), $endpoint));
    }

    public function authV2($username, $password): array
    {
        return DB::select(
            'SELECT id, name, username, password, is_admin, permissions, service_bodies FROM `users` WHERE `username` = ? AND `password` = SHA2(?, 256)',
            [$username, $password]
        );
    }

    public function getUserNameV1()
    {
        if (!isset($_SESSION['auth_user_name_string'])) {
            $url = sprintf('%s/local_server/server_admin/json.php?admin_action=get_user_info', $this->settings->getAdminBMLTRootServer());
            $get_user_info_response = json_decode($this->http->get($url, 3600));
            $user_name = isset($get_user_info_response->current_user) ? $get_user_info_response->current_user->name : $_SESSION['username'];
            $_SESSION['auth_user_name_string'] = $user_name;
        }
        return $_SESSION['auth_user_name_string'];
    }

    private function getCookiesFromHeaders(): array
    {
        $cookies = [];

        foreach ($GLOBALS['curlResponseHeaders']['set-cookie'] as $cookie) {
            array_push($cookies, explode(";", $cookie)[0]);
        }

        return $cookies;
    }
}