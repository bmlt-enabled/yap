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

        if (str_starts_with($request->path(), 'admin') && str_ends_with($request->path(), 'api.php')) {
            $content_type = 'application/json';
        } elseif (str_starts_with($request->path(), 'admin')) {
            $content_type = 'text/html';
        } else {
            $content_type = 'text/xml';
        }

        return response()->view('legacy', ['includePath' => $path])->header('content-Type', $content_type);
    }

    protected function renderPath($path, $content_type)
    {
        // view()->addNamespace('legacy', base_path('endpoints/'));

        if (file_exists($path)) {
            return response()->view('legacy', ['includePath' => $path, 'contentType' => $content_type]);
        } else {
            abort(404);
        }
    }
}
