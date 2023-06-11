<?php

namespace App\Services;

use App\Constants\AuthMechanism;
use App\Repositories\AuthenticationRepository;

class AuthenticationService
{
    protected AuthenticationRepository $authenticationRepository;
    protected SettingsService $settings;
    protected RootServerService $rootServer;
    protected SessionService $session;

    public function __construct(AuthenticationRepository $authenticationRepository, SettingsService $settings, RootServerService $rootServer, SessionService $session)
    {
        $this->authenticationRepository = $authenticationRepository;
        $this->settings = $settings;
        $this->rootServer = $rootServer;
        $this->session = $session;
    }

    public function authenticate() : bool
    {
        $auth_v2_result = $this->authenticationRepository->authV2($_POST['username'], $_POST['password']);
        if (count($auth_v2_result) == 1) {
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['auth_mechanism'] = AuthMechanism::V2;
            $_SESSION['auth_user_name_string'] = $auth_v2_result[0]->name;
            $_SESSION['auth_is_admin'] = $auth_v2_result[0]->is_admin;
            $_SESSION['auth_permissions'] = $auth_v2_result[0]->permissions;
            $_SESSION['auth_id'] = $auth_v2_result[0]->id;
            $_SESSION['auth_service_bodies'] = explode(",", $auth_v2_result[0]->service_bodies);
            $_SESSION['auth_service_bodies_rights'] = $this->rootServer->getServiceBodiesRightsIds();
            $this->session->setConfigForService($_SESSION['auth_service_bodies_rights'][0]);

            return true;
        } elseif ($this->settings->get("bmlt_auth") && $this->authenticationRepository->authV1($_POST['username'], $_POST['password'])) {
            $_SESSION['username'] = $_POST['username'];
            $_SESSION['auth_mechanism'] = AuthMechanism::V1;
            $_SESSION['auth_service_bodies_rights'] = $this->rootServer->getServiceBodiesRightsIds();
            $this->session->setConfigForService($_SESSION['auth_service_bodies_rights'][0]);
            return true;
        } else {
            return false;
        }
    }

    public function username()
    {
        return ""; //$this->authenticationRepository->GetUserNameV1();
    }
}
