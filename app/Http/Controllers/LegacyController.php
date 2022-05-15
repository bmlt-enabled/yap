<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegacyController extends Controller
{
    public function index(Request $request)
    {
        $path = base_path('/legacy/') . $request->path();

        // If the path does not end with PHP, assume we're requesting an index file
        if (substr($path, -3) != 'php') {
            $path .= '/index.php';
        }

        return $this->renderPath($path, str_starts_with($request->path(), 'admin') || str_ends_with($request->path(), 'clear-session.php') ? 'text/html' : str_ends_with($request->path(), 'upgrade-advisor.php') ? 'application/json' : 'text/xml');
    }

    public function msr($latitude, $longitude)
    {
        $path = base_path('/legacy/meeting-results.php');
        $_REQUEST['latitude'] = $latitude;
        $_REQUEST['longitude'] = $longitude;

        return response()->view('legacy', ['includePath' => $path])
            ->header('Content-Type', 'text/html');
    }

    protected function renderPath($path, $content_type)
    {
        // view()->addNamespace('legacy', base_path('endpoints/'));

        if (file_exists($path)) {
            return response()->view('legacy', ['includePath' => $path])
                ->header('Content-Type', $content_type);
        } else {
            abort(404);
        }
    }
}
