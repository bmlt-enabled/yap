<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function delete(Request $request)
    {
        foreach (Session::all() as $key => $value) {
            if (str_starts_with($key, "cache_") || str_starts_with($key, "override_") || str_starts_with($key, "call_state")) {
                Session::forget($key);
            }
        }

        return response("OK")
            ->header('Content-Type', 'text/plain');
    }
}
