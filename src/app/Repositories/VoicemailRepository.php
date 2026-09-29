<?php

namespace App\Repositories;

use App\Constants\EventId;
use App\Constants\EventStatusId;
use App\Models\RecordEvent;
use App\Models\EventStatus;
use App\Services\TwilioService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VoicemailRepository
{
    public function get($service_body_id): array
    {
        return RecordEvent::query()
            ->with(['record', 'session', 'eventStatus'])
            ->where('event_id', EventId::VOICEMAIL)
            ->where('service_body_id', $service_body_id)
            ->where(function ($query) {
                $query->whereHas('eventStatus', function ($q) {
                    $q->where('status', '<>', EventStatusId::VOICEMAIL_DELETED);
                })->orWhereDoesntHave('eventStatus');
            })
            ->get()
            ->map(function ($event) {
                return [
                    'callsid' => $event->callsid,
                    'pin' => $event->session?->pin,
                    'from_number' => $event->record?->from_number,
                    'to_number' => $event->record?->to_number,
                    'event_time' => $event->event_time . 'Z',
                    'meta' => $event->meta
                ];
            })
            ->toArray();
    }

    public function delete($service_body_id, $call_sid): bool
    {
        $event = RecordEvent::query()
            ->where('event_id', EventId::VOICEMAIL)
            ->where('service_body_id', $service_body_id)
            ->where('callsid', $call_sid)
            ->first();

        if (!$event) {
            return false;
        }

        $this->deleteTwilioRecording($event->meta);

        EventStatus::updateOrCreate(
            [
                'callsid' => $call_sid,
                'event_id' => EventId::VOICEMAIL
            ],
            [
                'status' => EventStatusId::VOICEMAIL_DELETED
            ]
        );

        return true;
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
