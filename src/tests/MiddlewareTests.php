<?php

namespace Tests;

use App\Repositories\ReportsRepository;
use Mockery;

class MiddlewareTests
{
    public function insertSession($callSid) : Mockery\MockInterface
    {
        $repository = Mockery::mock(ReportsRepository::class)->makePartial();
        $repository->shouldReceive('insertSession')->with($callSid);
        return $repository;
    }
}
