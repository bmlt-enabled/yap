<?php

namespace Tests;

use App\Services\RootServerService;

class RootServerMocks
{
    private array $serviceBodies;
    private RootServerService $service;

    public function __construct()
    {
        $this->serviceBodies[] = (object)[
            "id"=>"44",
            "parent_id"=>"43",
            "name"=>"Crossroads Area",
            "description"=>"Crossroads Area",
            "type"=>"AS",
            "url"=>"https://crossroadsarea.org",
            "helpline"=>"888-557-1667|ww1",
            "world_id"=>"AS1234",
        ];

        $this->service = mock(RootServerService::class)->makePartial();
        $this->service->shouldReceive("getServiceBodies")
            ->withNoArgs()->andReturn($this->serviceBodies);
    }

    public function getService()
    {
        return $this->service;
    }
}
