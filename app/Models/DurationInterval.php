<?php

namespace App\Models;

class DurationInterval
{
    public $hours;
    public $minutes;
    public $seconds;

    public function getDurationFormat()
    {
        return $this->hours . " hours " . $this->minutes . " minutes " . $this->seconds . " seconds";
    }
}
