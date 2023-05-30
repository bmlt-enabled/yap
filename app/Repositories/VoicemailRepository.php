<?php

namespace App\Repositories;

use App\Constants\EventId;
use App\Constants\EventStatusId;
use Illuminate\Support\Facades\DB;

class VoicemailRepository
{
    public function get($service_body_id): array
    {
        return DB::select(
            "SELECT r.`callsid`,s.`pin`,r.`from_number`,r.`to_number`,CONCAT(re.`event_time`, 'Z') as event_time,re.`meta` FROM records_events re
    LEFT OUTER JOIN records r ON re.callsid = r.callsid
    LEFT OUTER JOIN sessions s ON r.callsid = s.callsid
    LEFT OUTER JOIN event_status es ON re.callsid = es.callsid where re.event_id = ? and service_body_id = ? and (es.event_id = ? and es.status <> ? OR es.id IS NULL);",
            [EventId::VOICEMAIL, $service_body_id, EventId::VOICEMAIL, EventStatusId::VOICEMAIL_DELETED]
        );
    }
}
