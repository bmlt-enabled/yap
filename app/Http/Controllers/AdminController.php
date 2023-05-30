<?php

namespace App\Http\Controllers;

use App\Repositories\VoicemailRepository;
use App\Services\ConfigService;
use App\Utility\Sort;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;
use App\Services\RootServerService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use PHP_CodeSniffer\Config;

class AdminController extends Controller
{
    protected SettingsService $settings;
    protected RootServerService $rootServer;
    protected AuthenticationService $authn;
    protected AuthorizationService $authz;
    protected ConfigService $config;
    protected VoicemailRepository $voicemail;
    private array $pages = ["Home", "Reports", "Service Bodies", "Schedules", "Settings", "Volunteers", "Groups"];

    public function __construct(
        SettingsService       $settings,
        RootServerService     $rootServer,
        AuthenticationService $authn,
        AuthorizationService  $authz,
        ConfigService         $config,
        VoicemailRepository   $voicemail
    ) {
        $this->settings = $settings;
        $this->rootServer = $rootServer;
        $this->authn = $authn;
        $this->authz = $authz;
        $this->config = $config;
        $this->voicemail = $voicemail;

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
        return view(sprintf('admin/%s', $page), [
            "settings" => $this->settings,
            "rootServer" => $this->rootServer,
            "pages" => $this->pages,
            "username" => $this->authn->username(),
            "serviceBodiesForUser" => $serviceBodiesForUser,
            "serviceBodiesEnabledForRouting" => $serviceBodiesEnabledForRouting,
            "isTopLevelAdmin" => $this->authz->isTopLevelAdmin(),
            "voicemail" => $this->voicemail,
        ]);
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
