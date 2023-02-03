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
        session_destroy();
        session_write_close();
        return response("OK")
            ->header('Content-Type', 'text/plain')
            ->withCookie($cookie);
    }
}
