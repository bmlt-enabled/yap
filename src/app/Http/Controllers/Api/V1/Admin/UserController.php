<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    protected UserRepository $user;
    protected AuthorizationService $authz;

    public function __construct(UserRepository $user, AuthorizationService $authz)
    {
        $this->user = $user;
        $this->authz = $authz;
    }

    public function index(Request $request): Response
    {
        return response("not yet implemented");
    }

    public function store(Request $request): Response
    {
        if ($this->authz->canManageUsers()) {
            User::saveUser(
                $request->input('name'),
                $request->input('username'),
                $request->input('password'),
                $request->input('permissions'),
                $request->input('service_bodies'));
            return response(
                User::getUser($request->input('username')),
                200,
            );
        }

        return response("", 404);
    }

    public function update(Request $request): Response
    {
        $data = json_decode($request->getContent());
        if ($_SESSION['auth_id'] === $data->id) {
            $this->user->editUser($data, 'self');
        } else if ($this->authz->canManageUsers()) {
            $this->user->editUser($data, 'admin');
        } else {
            return response("", 404);
        }

        return response("");
    }

    public function destroy(Request $request, string $username): JsonResponse
    {
        if ($this->authz->canManageUsers()) {
            $response = User::deleteUser($username);
            if ($response === 1) {
                return response()->json(['message' => sprintf('User %s deleted successfully', $username)]);
            }
        }

        return response()->json(['message' => 'Not found'], 404);
    }
}
