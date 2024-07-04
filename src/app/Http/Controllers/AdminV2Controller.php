<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminV2Controller extends Controller
{
    public function index(Request $request)
    {
        return view('admin')
            ->with('baseUrl', sprintf("%s/adminv2", $request->getBaseUrl()))
            ->with('rootUrl', $request->getBaseUrl());
    }
}
