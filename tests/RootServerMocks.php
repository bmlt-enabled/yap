<?php

namespace Tests;

use App\Services\HttpService;
use App\Services\RootServerService;

class RootServerMocks
{
    private array $serviceBodies;
    public RootServerService $service;

    public function __construct($setEmptyHelplineNumber = false)
    {
        $helplineNumber = $setEmptyHelplineNumber ? null : "888-557-1667|ww1";

        $this->serviceBodies[] = (object)[
            "id"=>"1053",
            "parent_id"=>"1052",
            "name"=>"Finger Lakes Area Service",
            "description"=>"Finger Lakes Area Service",
            "type"=>"AS",
            "url"=>"http://www.flana.net",
            "helpline"=>$helplineNumber,
            "world_id"=>"AR50604",
        ];

        $this->service = mock(RootServerService::class, [app(HttpService::class)])->makePartial();
        $this->service->shouldReceive("getServiceBodies")
            ->withNoArgs()->andReturn($this->serviceBodies);
        $this->service->shouldReceive("getServiceBodiesForRouting")
            ->withAnyArgs()->andReturn($this->serviceBodies);
    }

    public function getService()
    {
        return $this->service;
    }
}
