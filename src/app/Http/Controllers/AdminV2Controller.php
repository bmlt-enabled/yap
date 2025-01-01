<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminV2Controller extends Controller
{
    public function index(Request $request)
    {
        return view('admin')
            ->with('baseUrl', "adminv2")
            ->with('rootUrl', $request->getBaseUrl());
    }
}
