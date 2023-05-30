<?php

namespace App\Utility;

class Format
{
    public static function getStringValue($subject)
    {
        if (is_bool($subject)) {
            if ($subject) {
                return "true";
            } else {
                return "false";
            }
        } elseif (is_array($subject)) {
            return strval(json_encode($subject));
        }

        return strval($subject);
    }
}
