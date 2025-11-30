<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 */
class UserController extends Controller
{
    protected AuthorizationService $authz;

    public function __construct(AuthorizationService $authz)
    {
        $this->authz = $authz;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{user}",
     *     tags={"Users"},
     *     summary="Get user details",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="Username of the user",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function show($username)
    {
        return User::getUser($username);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Get all users",
     *     @OA\Response(
     *         response=200,
     *         description="List of users retrieved successfully",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     )
     * )
     */
    public function index(Request $request): array|Collection
    {
        if ($this->authz->canManageUsers()) {
            return User::getUsers();
        }

        return [];
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "username", "password", "permissions", "service_bodies"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="service_bodies", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User created successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not authorized to manage users"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/users/{user}",
     *     tags={"Users"},
     *     summary="Update a user",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="Username of the user to update",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="service_bodies", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not authorized to update user"
     *     )
     * )
     *
     * @OA\Patch(
     *     path="/api/v1/users/{user}",
     *     tags={"Users"},
     *     summary="Partially update a user",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="Username of the user to update",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="service_bodies", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not authorized to update user"
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{user}",
     *     tags={"Users"},
     *     summary="Delete a user",
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="Username of the user to delete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not authorized to delete user"
     *     )
     * )
     */
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
