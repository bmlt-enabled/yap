<?php

namespace App\Services;

use App\Constants\AuthMechanism;
use App\Models\Session;
use App\Models\User;
use App\Repositories\AuthenticationRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

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
        // Attempt database authentication (authV2)
        $authV2Result = $this->authenticationRepository->authV2($username, $password);
        if (!empty($authV2Result)) {
            return $this->initializeSessionForAuthV2($authV2Result, $username);
        }

        // Attempt external authentication (authV1)
        if ($this->settings->get("bmlt_auth") && $this->authenticationRepository->authV1($username, $password)) {
            return $this->initializeSessionForAuthV1($username, $password);
        }

        return null; // Authentication failed
    }

    protected function initializeSessionForAuthV2(User $user, string $username): User
    {
        Auth::setUser($user); // Set user for the session

        session()->put([
            'username' => $username,
            'auth_mechanism' => AuthMechanism::V2,
            'auth_user_name_string' => $user->name,
            'auth_is_admin' => $user->is_admin,
            'auth_permissions' => $user->permissions,
            'auth_service_bodies' => explode(",", $user->service_bodies)
        ]);

        session()->put([
            'auth_service_bodies_rights' => $this->rootServer->getServiceBodiesRightsIds()
        ]);

        $this->session->setConfigForService(session()->get('auth_service_bodies_rights'));
        return $user;
    }

    protected function initializeSessionForAuthV1(string $username, string $password): ?User
    {
        session()->put([
            'username' => $username,
            'auth_mechanism' => AuthMechanism::V1,
        ]);

        $rights = $this->rootServer->getServiceBodiesRightsIds();
        if (empty($rights)) {
            return null;
        }

        $user = User::firstOrCreate(['username' => $username], [
            'name' => $username, // Default name for external users
            'password' => hash('sha256', $password),
            'is_admin' => false,
            'permissions' => 0,
            'service_bodies' => implode(',', $rights),
        ]);

        session()->put('auth_service_bodies_rights', $rights);

        Auth::setUser($user); // Set user for the session

        $this->session->setConfigForService($rights[0]);
        return $user;
    }
}
