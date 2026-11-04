<?php

namespace App\Repositories;

use App\Models\User;
use App\Services\HttpService;
use App\Services\RootServerService;
use App\Services\SettingsService;

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

    public function authV2($username, $password): ?User
    {
        // BMLT-backed rows have no yap password; their logins always go to BMLT.
        $user = User::where('username', $username)
            ->where('auth_source', User::AUTH_SOURCE_LOCAL)
            ->first();

        if (!$user || !$user->checkPassword($password)) {
            return null;
        }

        // Move legacy unsalted SHA-256 hashes (and outdated bcrypt costs) to
        // the current format while we have the plaintext.
        if ($user->passwordNeedsRehash()) {
            $user->password = User::hashPassword($password);
            $user->save();
        }

        return $user;
    }

    public function getUserNameV1()
    {
        return $this->rootServerService->getLoggedInUsername();
    }
}
