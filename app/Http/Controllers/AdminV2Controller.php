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

//    public function cacheClear()
//    {
//        // TODO: reimplement this with Laravel caching mechanism
//        if (isset($_SESSION['auth_is_admin']) && $_SESSION['auth_is_admin'] == 1) {
//            clearCache();
//            return response()->json([
//                'status' => 'cache cleared'
//            ]);
//        } else {
//            abort(404);
//        }
//    }
}
