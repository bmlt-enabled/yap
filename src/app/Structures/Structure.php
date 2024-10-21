<?php

namespace App\Structures;

abstract class Structure
{
    public function toArray()
    {
        return (array) $this;
    }
}
