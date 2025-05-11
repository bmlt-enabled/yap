<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Services\RootServerService;
use App\Utilities\Sort;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RootServerServiceBodiesForUserController extends Controller
{
    protected RootServerService $rootServerService;

    public function __construct(RootServerService $rootServerService)
    {
        $this->rootServerService = $rootServerService;
    }

    public function index(Request $request)
    {
        $serviceBodiesForUser =$this->rootServerService->getServiceBodiesForUser();
        Sort::sortOnField($serviceBodiesForUser, 'name');
        return $serviceBodiesForUser;
    }
}
