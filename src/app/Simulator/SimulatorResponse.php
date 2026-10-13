<?php

namespace App\Simulator;

class SimulatorResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly string $url,
    ) {
    }

    public function json(): array
    {
        return json_decode($this->body, true) ?? [];
    }
}
