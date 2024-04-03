<?php

namespace App\Utility;

class Sms
{
    public static function chunkSplit($msg)
    {
        $chunk_width = 1575;
        $chunks = wordwrap($msg, $chunk_width, '\n');
        return explode('\n', $chunks);
    }
}
