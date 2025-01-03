<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\DataType;
use App\Http\Controllers\Controller;
use App\Models\ConfigData;
use App\Services\ConfigService;
use App\Structures\ServiceBodyCallHandling;
use App\Utilities\Sort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;

class VolunteerRoutingEnabledController extends Controller
{
    protected ConfigService $config;

    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    public function index(Request $request): array
    {
        $serviceBodiesEnabledForRouting = $this->config->getVolunteerRoutingEnabledServiceBodies();
        Sort::sortOnField($serviceBodiesEnabledForRouting, 'service_body_name');
        return $serviceBodiesEnabledForRouting;
    }
}
