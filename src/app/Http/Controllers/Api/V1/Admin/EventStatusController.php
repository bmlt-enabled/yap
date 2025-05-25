<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventStatus;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="EventStatus",
 *     description="Event status management endpoints"
 * )
 */
class EventStatusController extends Controller
{
    private AuthorizationService $permissionService;
    private EventStatus $eventStatus;

    public function __construct(AuthorizationService $permissionService, EventStatus $eventStatus)
    {
        $this->permissionService = $permissionService;
        $this->eventStatus = $eventStatus;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/events/status",
     *     tags={"EventStatus"},
     *     summary="Get all event statuses",
     *     @OA\Response(
     *         response=200,
     *         description="List of event statuses retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="callsid", type="string"),
     *                 @OA\Property(property="event_id", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json($this->eventStatus::all())->header('Content-Type', 'application/json');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/events/status",
     *     tags={"EventStatus"},
     *     summary="Create a new event status",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"callsid", "event_id", "status"},
     *             @OA\Property(property="callsid", type="string", description="Call SID"),
     *             @OA\Property(property="event_id", type="integer", description="Event ID"),
     *             @OA\Property(property="status", type="string", description="Status of the event")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Event status created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="callsid", type="string"),
     *             @OA\Property(property="event_id", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to create event status"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        if ($this->permissionService->callsid($request->callsid, $request->event_id)) {
            $eventStatus = $this->eventStatus::create($request->all());
            return response()->json($eventStatus, 201)->header('Content-Type', 'application/json');
        } else {
            return response()->json(['error' => 'Unauthorized'], 403)->header('Content-Type', 'application/json');
        }
    }
}
