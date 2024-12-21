<?php
namespace App\Http\Controllers;

use App\Repositories\AuthenticationRepository;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->get('username');
        $password = $request->get('password');

        $token = $this->authn->authenticateApi($username, $password);

        if (!$token) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'token' => $token,
            'user' => auth()->user(),
        ]);
    }

    public function logout($auth = true)
    {
        $this->clearSession();
        return redirect(!$auth ? "/admin?auth=false" : "/admin");
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

    /**
     * @param $username
     * @return mixed
     */
    public function getUsername($username)
    {
        return $username;
    }
}
