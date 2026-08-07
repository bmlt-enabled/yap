<?php

use App\Constants\EventId;
use App\Models\RecordEvent;
use Tests\CallScenario;

test('records StirVerstat call event on initial webhook', function ($method) {
    $stirVerstat = 'TN-Validation-Passed-A';

    $scenario = (new CallScenario([]))
        ->withCallData(['StirVerstat' => $stirVerstat])
        ->startCall('+17325551212', '+12125551212', $method)
        ->assertHasEvent(EventId::STIR_VERSTAT_RECEIVED);

    $event = RecordEvent::where('callsid', $scenario->getCallSid())
        ->where('event_id', EventId::STIR_VERSTAT_RECEIVED)
        ->first();

    expect($event)->not->toBeNull();
    expect(json_decode($event->meta, true))->toBe(['stir_verstat' => $stirVerstat]);
})->with(['GET', 'POST']);
