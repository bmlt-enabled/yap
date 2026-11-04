<?php

namespace App\Services;

use App\Constants\AuthMechanism;
use App\Models\User;
use App\Repositories\AuthenticationRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * Admin login.
 *
 * The admin API authorizes every request from the Sanctum token's user (its
 * users row): is_admin, permissions and service_bodies. The session is never
 * read for permissions. For a BMLT login, that row is refreshed from BMLT here,
 * on every login, so BMLT stays the source of truth for those accounts.
 */
class AuthenticationService extends Service
{
    protected AuthenticationRepository $authenticationRepository;
    protected RootServerService $rootServer;
    protected SessionService $session;

    public function __construct(AuthenticationRepository $authenticationRepository, RootServerService $rootServer, SessionService $session)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->authenticationRepository = $authenticationRepository;
        $this->rootServer = $rootServer;
        $this->session = $session;
    }

    public function authenticateApi(string $username, string $password): ?User
    {
        // Start from an empty session so nothing from an earlier login in this
        // browser (BMLT cookies, service body overrides) carries over.
        session()->flush();

        $user = $this->attempt($username, $password);
        if (!$user) {
            // Drop the BMLT cookies a refused BMLT login may have stored.
            session()->flush();
        }

        return $user;
    }

    private function attempt(string $username, string $password): ?User
    {
        // Database authentication (authV2). Only matches local accounts.
        $authV2Result = $this->authenticationRepository->authV2($username, $password);
        if (!empty($authV2Result)) {
            return $this->initializeSessionForAuthV2($authV2Result, $username);
        }

        // BMLT authentication (authV1).
        if ($this->settings->get("bmlt_auth") && $this->authenticationRepository->authV1($username, $password)) {
            return $this->initializeSessionForAuthV1($username);
        }

        return null;
    }

    protected function initializeSessionForAuthV2(User $user, string $username): User
    {
        Auth::setUser($user);

        session()->put([
            'username' => $username,
            'auth_mechanism' => AuthMechanism::V2,
            'auth_user_name_string' => $user->name,
        ]);

        $rights = $this->rootServer->getServiceBodiesRightsIds();
        if (!empty($rights)) {
            $this->session->setConfigForService($rights[0]);
        }
        return $user;
    }

    protected function initializeSessionForAuthV1(string $username): ?User
    {
        // Never attach a BMLT login to a local account: that would hand the
        // local user's token (and admin rights) to whoever holds the BMLT
        // password for the same name.
        $existing = User::where('username', $username)->first();
        if ($existing && !$existing->isBmltUser()) {
            return null;
        }

        $rights = $this->rootServer->getBmltServiceBodiesRightsIds();
        if (empty($rights)) {
            return null;
        }

        $user = User::syncBmltUser($username, $rights);
        Auth::setUser($user);

        session()->put([
            'username' => $username,
            'auth_mechanism' => AuthMechanism::V1,
        ]);

        $this->session->setConfigForService($rights[0]);
        return $user;
    }

    public function logout(?User $user): void
    {
        if ($user?->isBmltUser()) {
            $this->authenticationRepository->logoutV1();
        }

        session()->flush();
    }
}
