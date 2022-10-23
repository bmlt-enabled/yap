<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PingController extends Controller
{
    public function index()
    {
        return response("PONG")->header('Content-Type', 'text/plain');
    }
}
