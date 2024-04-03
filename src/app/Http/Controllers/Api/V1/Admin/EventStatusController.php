<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventStatus;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EventStatusController extends Controller
{
    private AuthorizationService $permissionService;
    private EventStatus $eventStatus;

    public function __construct(AuthorizationService $permissionService, EventStatus $eventStatus)
    {
        $this->permissionService = $permissionService;
        $this->eventStatus = $eventStatus;
    }

    public function index()
    {
        return response()->json($this->eventStatus::all())->header('Content-Type', 'application/json');
    }

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
