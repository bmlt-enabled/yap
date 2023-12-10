<?php

namespace App\Http\Controllers;

class PingController extends Controller
{
    public function index()
    {
        return response("PONG")->header('Content-Type', 'text/plain');
    }
}
