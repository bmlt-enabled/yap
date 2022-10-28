<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function delete()
    {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, "cache_") === 0
                || strpos($key, "override_") === 0
                || strpos($key, 'call_state') === 0) {
                echo sprintf("Cleared <i>%s</i><br/>", $key);
                unset($_SESSION[$key]);
            }
        }
        return response("OK")->header('Content-Type', 'text/plain');
    }
}
