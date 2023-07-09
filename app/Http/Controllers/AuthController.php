<?php
namespace App\Http\Controllers;

use App\Constants\AuthMechanism;
use App\Constants\Http;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;
use App\Services\SettingsService;

class AuthController extends Controller
{
    protected AuthorizationService $authz;
    protected AuthenticationService $authn;
    protected SettingsService $settings;

    public function __construct(
        AuthorizationService  $authz,
        AuthenticationService $authn,
        SettingsService       $settings
    ) {
        $this->authz = $authz;
        $this->authn = $authn;
        $this->settings = $settings;
    }

    public function logout($auth = true)
    {
        $this->clearSession();
        return redirect(!$auth ? "/admin?auth=false" : "/admin");
    }

    public function timeout()
    {
        $this->clearSession();
        return redirect("/admin?expired=true");
    }

    public function invalid()
    {
        return self::logout(false);
    }

    private function clearSession(): void
    {
        $this->authn->logout();
    }

    public function rights()
    {
        $rights = $this->authz->getServiceBodyRights();
        return $rights ?? response()->json(['error' => 'Unauthorized'], 403);
    }
}
