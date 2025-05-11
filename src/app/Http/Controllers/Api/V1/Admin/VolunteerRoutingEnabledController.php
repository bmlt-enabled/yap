<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;
use App\Utilities\Sort;

class VolunteerRoutingEnabledController extends Controller
{
    protected ConfigService $config;

    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    public function index(): array
    {
        $serviceBodiesEnabledForRouting = $this->config->getVolunteerRoutingEnabledServiceBodies();
        Sort::sortOnField($serviceBodiesEnabledForRouting, 'name');
        return $serviceBodiesEnabledForRouting;
    }
}
