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

/**
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
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

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     tags={"Authentication"},
     *     summary="Login to get a Bearer Token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", format="username", example="username"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="Bearer your_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = $this->authn->authenticateApi(
            $request->input('username'),
            $request->input('password')
        );

        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'token' => $user->createToken('API Token')->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user",
     *     tags={"Authentication"},
     *     summary="Get current authenticated user",
     *     description="Returns the currently authenticated user's information",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Current user information",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="is_admin", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
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
