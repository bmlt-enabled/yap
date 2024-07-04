<?php

namespace App\Structures;

class RecordType
{
    const PHONE = 1;
    const SMS = 2;

    public static function getTypeById($id)
    {
        switch ($id) {
            case RecordType::PHONE:
                return "CALL";
            case RecordType::SMS:
                return "SMS";
        }
    }
}
