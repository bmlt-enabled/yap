<?php

namespace App\Structures;

class Group extends Structure
{
    public string $group_name;
    public array $group_shared_service_bodies = [];

    public function __construct($group = null)
    {
        if ($group) {
            // Dynamically assign all properties from the passed group object
            foreach (get_object_vars($group) as $property => $value) {
                $this->$property = $value;
            }
        } else {
            // Optionally, set default values here
            $this->group_name = "";
            $this->group_shared_service_bodies = [];
        }
    }
}
