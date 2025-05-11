<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class SessionController extends Controller
{
    public function store()
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
