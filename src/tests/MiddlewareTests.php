<?php

namespace Tests;

use App\Constants\DataType;
use App\Repositories\ConfigRepository;
use App\Repositories\ReportsRepository;
use Mockery;

class MiddlewareTests
{
    public function getAllDbData($id, $serviceBodyId, $parentServiceBodyId, $group) : Mockery\MockInterface
    {
        $repository = Mockery::mock(ConfigRepository::class);
        $repository->shouldReceive("getAllDbData")->with(
            DataType::YAP_CONFIG_V2
        )->andReturn([(object)[
            "service_body_id" => $serviceBodyId,
            "id" => $id,
            "parent_id" => $parentServiceBodyId,
            "data" => json_encode($group)
        ]]);
        return $repository;
    }

    public function insertSession($callSid) : Mockery\MockInterface
    {
        $repository = Mockery::mock(ReportsRepository::class)->makePartial();
        $repository->shouldReceive('insertSession')->with($callSid);
        return $repository;
    }
}
