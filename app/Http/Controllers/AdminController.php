<?php
namespace App\Http\Controllers;

use App\Constants\AuthMechanism;
use App\Constants\Http;
use App\Services\PermissionService;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin');
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
