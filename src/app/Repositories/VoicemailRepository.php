<?php

namespace App\Repositories;

use App\Constants\EventId;
use App\Constants\EventStatusId;
use App\Models\RecordEvent;
use App\Models\EventStatus;
use Illuminate\Support\Facades\DB;

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
                    'callsid' => $event->record?->callsid,
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
            ->whereHas('record', function ($query) use ($call_sid) {
                $query->where('callsid', $call_sid);
            })
            ->first();

        if (!$event) {
            return false;
        }

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
}
