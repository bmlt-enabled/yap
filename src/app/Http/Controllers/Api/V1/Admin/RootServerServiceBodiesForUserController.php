<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Services\RootServerService;
use App\Utilities\Sort;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * @OA\Tag(
 *     name="RootServer",
 *     description="Root server integration endpoints"
 * )
 */
class RootServerServiceBodiesForUserController extends Controller
{
    protected RootServerService $rootServerService;

    public function __construct(RootServerService $rootServerService)
    {
        $this->rootServerService = $rootServerService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rootServer/serviceBodies/user",
     *     tags={"RootServer"},
     *     summary="Get service bodies for authenticated user",
     *     description="Retrieves all service bodies that the authenticated user has access to from the root server",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Service bodies retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true),
     *                 @OA\Property(property="url", type="string", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $serviceBodiesForUser =$this->rootServerService->getServiceBodiesForUser();
        Sort::sortOnField($serviceBodiesForUser, 'name');
        return $serviceBodiesForUser;
    }
}
