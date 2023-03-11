<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        return view('admin')->with('baseUrl', sprintf("%s/adminv2", $request->getBaseUrl()));
    }

    public function cacheClear()
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        if (isset($_SESSION['auth_is_admin']) && $_SESSION['auth_is_admin'] == 1) {
            clearCache();
            return response()->json([
                'status' => 'cache cleared'
            ]);
        } else {
            abort(404);
        }
    }
}
