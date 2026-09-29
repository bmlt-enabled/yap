<?php

namespace App\Repositories;

use App\Constants\EventId;
use App\Constants\EventStatusId;
use App\Models\EventStatus;
use App\Models\RecordEvent;
use App\Services\TwilioService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VoicemailRepository
{
    public function get($service_body_id): array
    {
        return DB::select(
            "SELECT re.`callsid`,s.`pin`,r.`from_number`,r.`to_number`,CONCAT(re.`event_time`, 'Z') as event_time,re.`meta` FROM records_events re
    LEFT OUTER JOIN records r ON re.callsid = r.callsid
    LEFT OUTER JOIN sessions s ON r.callsid = s.callsid
    LEFT OUTER JOIN event_status es ON re.callsid = es.callsid where re.event_id = ? and service_body_id = ? and (es.event_id = ? and es.status <> ? OR es.id IS NULL);",
            [EventId::VOICEMAIL, $service_body_id, EventId::VOICEMAIL, EventStatusId::VOICEMAIL_DELETED]
        );
    }

    /**
     * Remove the Twilio recording, then hide the voicemail from the admin list.
     * A recording Twilio has already removed (HTTP 404) is treated as success.
     */
    public function delete(string $callsid): ?EventStatus
    {
        $event = RecordEvent::query()
            ->where('event_id', EventId::VOICEMAIL)
            ->where('callsid', $callsid)
            ->first();

        if (!$event) {
            return null;
        }

        $this->deleteTwilioRecording($event->meta);

        return EventStatus::updateOrCreate(
            [
                'callsid' => $callsid,
                'event_id' => EventId::VOICEMAIL,
            ],
            [
                'status' => EventStatusId::VOICEMAIL_DELETED,
            ]
        );
    }

    public static function recordingSidFromMeta(?string $meta): ?string
    {
        if ($meta === null || $meta === '') {
            return null;
        }

        $decoded = json_decode($meta);
        if (!is_object($decoded)) {
            throw new RuntimeException('Voicemail recording URL is missing a recording SID');
        }

        $url = $decoded->url ?? null;
        if (!is_string($url) || $url === '') {
            return null;
        }

        if (preg_match('#/Recordings/(RE[0-9a-fA-F]+)#', $url, $matches)) {
            return $matches[1];
        }

        throw new RuntimeException('Voicemail recording URL is missing a recording SID');
    }

    private function deleteTwilioRecording(?string $meta): void
    {
        $recordingSid = self::recordingSidFromMeta($meta);
        if ($recordingSid === null) {
            return;
        }

        app(TwilioService::class)->deleteRecording($recordingSid);
    }
}
