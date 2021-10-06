<?php

namespace App\Http\Controllers;

use App\Models\EventStatus;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class EventStatusController extends Controller
{
    private $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        return EventStatus::all();
    }

    public function set(Request $request)
    {
        if ($this->permissionService->callsid($request->callsid)) {
            $eventStatus = EventStatus::create($request->all());
            return response()->json($eventStatus, 201);
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    }
}
