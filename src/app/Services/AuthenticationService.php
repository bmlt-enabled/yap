<?php

namespace App\Services;

use App\Constants\AuthMechanism;
use App\Constants\Http;
use App\Repositories\AuthenticationRepository;
use Illuminate\Support\Facades\App;

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

    public function authenticate($username, $password) : bool
    {
        $auth_v2_result = $this->authenticationRepository->authV2($username, $password);
        if (count($auth_v2_result) == 1) {
            $_SESSION['username'] = $username;
            $_SESSION['auth_mechanism'] = AuthMechanism::V2;
            $_SESSION['auth_user_name_string'] = $auth_v2_result[0]->name;
            $_SESSION['auth_is_admin'] = $auth_v2_result[0]->is_admin;
            $_SESSION['auth_permissions'] = $auth_v2_result[0]->permissions;
            $_SESSION['auth_id'] = $auth_v2_result[0]->id;
            $_SESSION['auth_service_bodies'] = explode(",", $auth_v2_result[0]->service_bodies);
            $_SESSION['auth_service_bodies_rights'] = $this->rootServer->getServiceBodiesRightsIds();
            $this->session->setConfigForService($_SESSION['auth_service_bodies_rights'][0]);

            return true;
        } elseif ($this->settings->get("bmlt_auth") && $this->authenticationRepository->authV1($username, $password)) {
            $_SESSION['username'] = $username;
            $_SESSION['auth_mechanism'] = AuthMechanism::V1;
            $rights = $this->rootServer->getServiceBodiesRightsIds();
            if (count($rights)) {
                $_SESSION['auth_service_bodies_rights'] = $rights;
                $this->session->setConfigForService($rights[0]);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function verify() : bool
    {
        $verified = false;
        if (isset($_SESSION['auth_mechanism'])) {
            if ($_SESSION['auth_mechanism'] == AuthMechanism::V1) {
                $verified = $this->authenticationRepository->verifyV1();
            } else {
                $verified = true;
            }
        }

        return $verified;
    }

    public function logout(): void
    {
        if (isset($_SESSION['auth_mechanism']) && $_SESSION['auth_mechanism'] == AuthMechanism::V1) {
            if (isset($_SESSION['bmlt_auth_session']) && $_SESSION['bmlt_auth_session'] != null) {
                $this->authenticationRepository->logoutV1();
            }

            session_unset();
        } else {
            session_unset();
        }
    }

    public function username()
    {
        return $this->authenticationRepository->GetUserNameV1();
    }
}
