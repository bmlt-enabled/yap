<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordsEvents extends Model
{
    protected $table = 'records_events';
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = ["callsid", "event_time", "event_id", "service_body_id", "meta", "type"];

    public static function generate($callSid, $eventId, $eventTime, $serviceBodyId, $meta, $type) : void
    {
        self::create([
            'callsid'=>$callSid,
            'event_time'=>$eventTime,
            'event_id'=>$eventId,
            'service_body_id'=>$serviceBodyId,
            'meta'=>$meta,
            'type'=>$type
        ]);
    }
}
