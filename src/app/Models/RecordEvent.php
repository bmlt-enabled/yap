<?php

namespace App\Models;

use App\Constants\EventId;
use Illuminate\Database\Eloquent\Model;

class RecordEvent extends Model
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

    public static function getMapMetrics($service_body_ids, $date_range_start, $date_range_end)
    {
        return self::whereBetween('event_time', [$date_range_start, $date_range_end])
            ->whereIn('event_id', [EventId::VOLUNTEER_SEARCH, EventId::MEETING_SEARCH_LOCATION_GATHERED])
            ->whereNotNull('meta')
            ->whereIn('service_body_id', $service_body_ids)
            ->select(['event_id', 'meta'])
            ->get()
            ->all();
    }
}
