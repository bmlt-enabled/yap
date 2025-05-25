<?php

namespace App\Models;

use App\Constants\EventId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecordEvent extends Model
{
    protected $table = 'records_events';
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = ["callsid", "event_time", "event_id", "service_body_id", "meta", "type"];

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class, 'callsid', 'callsid');
    }

    public function session(): HasOne
    {
        return $this->hasOne(Session::class, 'callsid', 'callsid');
    }

    public function eventStatus(): HasOne
    {
        return $this->hasOne(EventStatus::class, 'callsid', 'callsid')
            ->where('event_id', EventId::VOICEMAIL);
    }

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
