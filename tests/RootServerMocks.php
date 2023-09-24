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
            "id"=>"44",
            "parent_id"=>"43",
            "name"=>"Crossroads Area",
            "description"=>"Crossroads Area",
            "type"=>"AS",
            "url"=>"https://crossroadsarea.org",
            "helpline"=>$helplineNumber,
            "world_id"=>"AS1234",
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
