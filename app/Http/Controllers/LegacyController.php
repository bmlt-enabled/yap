<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegacyController extends Controller
{
    public function admin($path = "index.php") {
        ob_start();
        include base_path("backend/$path");
        return response( ob_get_clean() );
    }

    public function endpoints($path = "index.php") {
        ob_start();
        include base_path("endpoints/$path");
        return response( ob_get_clean() )
            ->header("Content-Type", "text/xml");;
    }
}
