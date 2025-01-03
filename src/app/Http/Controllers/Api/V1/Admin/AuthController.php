<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected AuthorizationService $authz;
    protected AuthenticationService $authn;

    public function __construct(
        AuthorizationService  $authz,
        AuthenticationService $authn,
    ) {
        $this->authz = $authz;
        $this->authn = $authn;
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        return response()->json([
            'status' => 'success',
            'token' => $user->createToken('API Token')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $this->authn->logout();
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function rights()
    {
        $rights = $this->authz->getServiceBodyRights();
        return $rights ?? response()->json(['error' => 'Unauthorized'], 403);
    }
}
