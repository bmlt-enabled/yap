<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function delete(Request $request)
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        $cookie = cookie(session_id(), "", time() - 3600);

        foreach ($_SESSION as $key => $value) {
            if (strpos($key, "cache_") === 0
                || strpos($key, "override_") === 0
                || strpos($key, 'call_state') === 0) {
                unset($_SESSION[$key]);
            }
        }

        return response("OK")
            ->header('Content-Type', 'text/plain')
            ->withCookie($cookie);
    }
}
