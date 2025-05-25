<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;
use App\Utilities\Sort;

/**
 * @OA\Tag(
 *     name="VolunteerRouting",
 *     description="Volunteer routing configuration endpoints"
 * )
 */
class VolunteerRoutingEnabledController extends Controller
{
    protected ConfigService $config;

    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/volunteers/routing/enabled",
     *     tags={"VolunteerRouting"},
     *     summary="Get service bodies enabled for volunteer routing",
     *     @OA\Response(
     *         response=200,
     *         description="List of service bodies enabled for volunteer routing",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="url", type="string"),
     *                 @OA\Property(property="helpline", type="string"),
     *                 @OA\Property(property="world_id", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): array
    {
        $serviceBodiesEnabledForRouting = $this->config->getVolunteerRoutingEnabledServiceBodies();
        Sort::sortOnField($serviceBodiesEnabledForRouting, 'name');
        return $serviceBodiesEnabledForRouting;
    }
}
