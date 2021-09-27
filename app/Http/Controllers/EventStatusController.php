<?php

namespace App\Http\Controllers;

use App\Models\EventStatus;
use Illuminate\Http\Request;

class EventStatusController extends Controller
{
    public function index()
    {
        return EventStatus::get
    }

    public function set(Request $request)
    {
        $eventStatus = EventStatus::create($request->all());

        return response()->json($eventStatus, 201);
    }
}
