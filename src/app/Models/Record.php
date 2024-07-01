<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    protected $primaryKey = "id";
    protected $table = "records";
    public $timestamps = false;
    protected $fillable = ["callsid", "start_time", "end_time", "from_number", "to_number", "payload", "duration", "type"];

    public static function generate($callsid, $start_time, $end_time, $from_number, $to_number, $payload, $duration, $type) : void
    {
        self::create([
            "callsid"=>$callsid,
            "start_time"=>$start_time,
            "end_time"=>$end_time,
            "from_number"=>$from_number,
            "to_number"=>$to_number,
            "payload"=>$payload,
            "duration"=>$duration,
            "type"=>$type
        ]);
    }
}
