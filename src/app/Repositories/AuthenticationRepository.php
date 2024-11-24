<?php

namespace App\Repositories;

use App\Services\HttpService;
use App\Services\RootServerService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;

class AuthenticationRepository
{
    protected SettingsService $settings;
    protected RootServerService $rootServerService;
    protected HttpService $http;

    public function __construct(
        SettingsService $settings,
        RootServerService $rootServerService,
        HttpService $http
    ) {
        $this->settings = $settings;
        $this->rootServerService = $rootServerService;
        $this->http = $http;
    }

    public function authV1($username, $password): bool
    {
        return $this->rootServerService->authenticate($username, $password);
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
        $this->rootServerService->logout();
    }

    public function authV2($username, $password): array
    {
        $passwordHash = hash('sha256', $password);
        return DB::select(
            'SELECT id, name, username, password, is_admin, permissions, service_bodies FROM `users` WHERE `username` = ? AND `password` = ?',
            [$username, $passwordHash]
        );
    }

    public function getUserNameV1()
    {
        return $this->rootServerService->getLoggedInUsername();
    }
}
