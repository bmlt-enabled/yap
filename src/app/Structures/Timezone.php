<?php

namespace App\Structures;

class Timezone
{
    public string $dstOffset;
    public string $rawOffset;
    public string $status;
    public string $timeZoneId;
    public string $timeZoneName;

    public function __construct(string $status, string $dstOffset, string $rawOffset, string $timeZoneId, string $timeZoneName)
    {
        $this->status = $status;
        $this->dstOffset = $dstOffset;
        $this->rawOffset = $rawOffset;
        $this->timeZoneId = $timeZoneId;
        $this->timeZoneName = $timeZoneName;
    }
}
