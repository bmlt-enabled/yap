<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected AuthorizationService $authz;

    public function __construct(AuthorizationService $authz)
    {
        $this->authz = $authz;
    }

    public function show($username)
    {
        return User::getUser($username);
    }

    public function index(Request $request): array|Collection
    {
        return User::getUsers();
    }

    public function store(Request $request): JsonResponse
    {
        if ($this->authz->canManageUsers()) {
            User::saveUser(
                $request->input('name'),
                $request->input('username'),
                $request->input('password'),
                $request->input('permissions'),
                $request->input('service_bodies')
            );
            return response()->json(
                User::getUser($request->input('username'))
            );
        }

        return response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, string $username): JsonResponse
    {
        if (session()->get('username') === $username) {
            $user = User::editUserForSelf(
                $request->input('name'),
                $username,
                $request->input('password', "") ?? ""
            );
        } else if ($this->authz->canManageUsers()) {
            $user = User::editUserForAdmin(
                $request->input('name'),
                $username,
                $request->input('password', "") ?? "",
                $request->input('permissions'),
                $request->input('service_bodies')
            );
        } else {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(User::getUser($username));
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
