<?php

namespace App\Http\Controllers;

use App\Services\CallService;
use App\Services\ConfigService;
use App\Services\UpgradeAdvisorService;
use App\Utility\Sort;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;
use App\Services\RootServerService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected SettingsService $settings;
    protected RootServerService $rootServer;
    protected AuthenticationService $authn;
    protected AuthorizationService $authz;
    protected ConfigService $config;
    protected CallService $call;
    protected UpgradeAdvisorService $upgradeAdvisor;
    private array $pages = ["Home", "Reports", "Service Bodies", "Schedules", "Settings", "Volunteers", "Groups"];

    public function __construct(
        SettingsService       $settings,
        RootServerService     $rootServer,
        AuthenticationService $authn,
        AuthorizationService  $authz,
        ConfigService         $config,
        CallService           $call,
        UpgradeAdvisorService $upgradeAdvisor
    ) {
        $this->settings = $settings;
        $this->rootServer = $rootServer;
        $this->authn = $authn;
        $this->authz = $authz;
        $this->config = $config;
        $this->call = $call;
        $this->upgradeAdvisor = $upgradeAdvisor;

        if ($authz->canManageUsers()) {
            $this->pages[] = "Users";
        }
    }

    public function index(Request $request)
    {
        $serviceBodiesForUser =$this->rootServer->getServiceBodiesForUser();
        Sort::sortOnField($serviceBodiesForUser, 'name');

        $serviceBodiesEnabledForRouting = $this->config->getVolunteerRoutingEnabledServiceBodies();
        Sort::sortOnField($serviceBodiesEnabledForRouting, 'service_body_name');

        $page = $request->route("page") == "" ? "index" : $request->route("page");

        $data = [
            "canManageUsers" => $this->authz->canManageUsers(),
            "isTopLevelAdmin" => $this->authz->isTopLevelAdmin(),
            "pages" => $this->pages,
            "rootServer" => $this->rootServer,
            "serviceBodiesForUser" => $serviceBodiesForUser,
            "serviceBodiesEnabledForRouting" => $serviceBodiesEnabledForRouting,
            "settings" => $this->settings,
            "users" => $this->config->getUsers(),
            "voicemail" => $this->call->getVoicemail(),
        ];

        if ($page != "index") {
            $data = array_merge($data, ["username" => $this->authn->username()]);
        }

        if ($page == "home" || $page == "index") {
            $data = array_merge($data, ["status" => $this->upgradeAdvisor->getStatus()]);
        }

        return view(sprintf('admin/%s', $page), $data);
    }

    public function login(Request $request): \Illuminate\Routing\Redirector|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse
    {
        $auth = $this->authn->authenticate();
        if ($auth) {
            return redirect("admin/home");
        } else {
            return redirect("admin/auth/invalid");
        }
    }
}
