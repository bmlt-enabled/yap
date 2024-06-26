<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;

class SwaggerController extends Controller
{
    public function openapi(Request $request)
    {
        $server = new stdClass;
        $server->url = rtrim(url('/'), '/') . '/';
        $server->description = 'this server';

        $json = json_decode(File::get(storage_path('api-docs/api-docs.json')));
        $json->servers = [$server];

        return new JsonResponse($json);
    }
}
