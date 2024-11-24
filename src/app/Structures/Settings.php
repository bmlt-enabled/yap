<?php

namespace App\Structures;

class Settings extends Structure
{
    public function __construct($config = null)
    {
        if ($config) {
            // Dynamically assign all properties from the passed group object
            foreach (get_object_vars($config) as $property => $value) {
                $this->$property = $value;
            }
        }
    }
}
