<?php

namespace App\Utility;

class Sort
{
    public static function sortOnField(&$objects, $on, $order = 'ASC') : void
    {
        usort($objects, function ($a, $b) use ($on, $order) {
            return $order === 'DESC' ? -strcoll($a->{$on}, $b->{$on}) : strcoll($a->{$on}, $b->{$on});
        });
    }
}
