<?php

namespace App\Http\Controllers;

use App\Constants\AuthMechanism;
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
    private array $pages = ["Home", "Reports", "Service Bodies", "Schedules", "Settings", "Volunteers", "Groups"];

    public function __construct(SettingsService $settings, RootServerService $rootServer, AuthenticationService $authn, AuthorizationService $authz)
    {
        $this->settings = $settings;
        $this->rootServer = $rootServer;
        $this->authn = $authn;
        $this->authz = $authz;

        if ($authz->canManageUsers()) {
            $this->pages[] = "Users";
        }
    }

    public function index(Request $request)
    {
        return view('admin/index', [
            "settings" => $this->settings,
            "rootServer" => $this->rootServer
        ]);
    }

    public function home(Request $request)
    {
        return view('admin/home', [
            "settings" => $this->settings,
            "rootServer" => $this->rootServer,
            "pages" => $this->pages,
            "username" => $this->authn->username(),
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
