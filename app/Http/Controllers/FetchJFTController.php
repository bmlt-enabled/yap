<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FetchJFTController extends Controller
{

    public function index()
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        return response()->view("jft", [
            "jft_array" => get_jft(),
            "voice" => voice(),
            "language" => setting('language')])->header("Content-Type", "text/xml");
    }
}
