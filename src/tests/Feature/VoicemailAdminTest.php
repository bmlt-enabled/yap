<?php

use App\Constants\EventId;
use App\Constants\EventStatusId;
use App\Models\EventStatus;
use App\Models\Record;
use App\Models\RecordEvent;
use App\Models\User;
use App\Repositories\VoicemailRepository;
use App\Services\TwilioService;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Twilio\Exceptions\RestException;

function insertVoicemailEvent(
    string $callsid,
    int $serviceBodyId = 44,
    ?string $recordingSid = 'RE0123456789abcdef0123456789abcdef',
    bool $withRecord = false,
    ?string $url = null
): void {
    if ($url === null && $recordingSid !== null) {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/AC222a79bf52fdc8c3cf463b2846582b83/Recordings/' . $recordingSid;
    }
    RecordEvent::generate(
        $callsid,
        EventId::VOICEMAIL,
        Carbon::now(),
        $serviceBodyId,
        json_encode(['url' => $url]),
        null
    );
    if ($withRecord) {
        Record::generate(
            $callsid,
            Carbon::now(),
            Carbon::now(),
            '+15551110000',
            '+15552220000',
            '',
            5,
            1
        );
    }
}

function mockRecordingDeletes(array $results): void
{
    $client = Mockery::mock(\Twilio\Rest\Client::class);
    foreach ($results as $sid => $status) {
        $context = Mockery::mock();
        if ($status === 404 || $status === 500) {
            $context->shouldReceive('delete')->once()->andThrow(
                new RestException('[HTTP ' . $status . ']', $status, $status)
            );
        } else {
            $context->shouldReceive('delete')->once()->andReturn(true);
        }
        $client->shouldReceive('recordings')->once()->with($sid)->andReturn($context);
    }
    $twilio = Mockery::mock(TwilioService::class)->makePartial();
    $twilio->shouldReceive('client')->andReturn($client);
    app()->instance(TwilioService::class, $twilio);
}

function actAsVoicemailAdmin(): void
{
    Sanctum::actingAs(User::factory()->admin()->create());
}

test('voicemail list uses the event callsid when there is no call record', function () {
    actAsVoicemailAdmin();
    $callsid = 'CA11111111111111111111111111111111';
    insertVoicemailEvent($callsid);

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonPath('data.0.callsid', $callsid)
        ->assertJsonPath('data.0.from_number', null)
        ->assertJsonPath('data.0.to_number', null)
        ->assertJsonPath('data.0.pin', null);
});

test('voicemail list keeps caller fields when a call record exists', function () {
    actAsVoicemailAdmin();
    $callsid = 'CA22222222222222222222222222222222';
    insertVoicemailEvent($callsid, 44, 'RE0123456789abcdef0123456789abcdef', true);

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonPath('data.0.callsid', $callsid)
        ->assertJsonPath('data.0.from_number', '+15551110000')
        ->assertJsonPath('data.0.to_number', '+15552220000');
});

test('recording sid is taken from the voicemail meta url', function () {
    $sid = 'RE0123456789abcdef0123456789abcdef';
    $meta = json_encode([
        'url' => 'https://api.twilio.com/2010-04-01/Accounts/ACxx/Recordings/' . $sid . '.mp3',
    ]);

    expect(VoicemailRepository::recordingSidFromMeta($meta))->toBe($sid);
    expect(VoicemailRepository::recordingSidFromMeta(json_encode(['url' => null])))->toBeNull();
    expect(fn () => VoicemailRepository::recordingSidFromMeta(json_encode(['url' => 'https://example.com/nope'])))
        ->toThrow(RuntimeException::class);
});

test('deleting a voicemail with no call record removes the Twilio recording and hides the row', function () {
    actAsVoicemailAdmin();
    $callsid = 'CA33333333333333333333333333333333';
    $recordingSid = 'REaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    insertVoicemailEvent(
        $callsid,
        44,
        $recordingSid,
        false,
        'https://api.twilio.com/2010-04-01/Accounts/ACxx/Recordings/' . $recordingSid . '.mp3'
    );
    mockRecordingDeletes([$recordingSid => 200]);

    $this->deleteJson('/api/v1/voicemail/' . $callsid . '?serviceBodyId=44')
        ->assertOk()
        ->assertJson(['message' => 'Voicemail recording permanently deleted from Twilio']);

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonCount(0, 'data');
    $status = EventStatus::where('callsid', $callsid)->first();
    expect($status->event_id)->toBe(EventId::VOICEMAIL);
    expect((int) $status->status)->toBe(EventStatusId::VOICEMAIL_DELETED);
});

test('deleting a voicemail that has a call record still removes the Twilio recording', function () {
    actAsVoicemailAdmin();
    $callsid = 'CA44444444444444444444444444444444';
    $recordingSid = 'REbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    insertVoicemailEvent($callsid, 44, $recordingSid, true);
    mockRecordingDeletes([$recordingSid => 200]);

    $this->deleteJson('/api/v1/voicemail/' . $callsid . '?serviceBodyId=44')->assertOk();

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('a Twilio 404 is treated as a successful voicemail delete', function () {
    actAsVoicemailAdmin();
    $callsid = 'CA55555555555555555555555555555555';
    $recordingSid = 'REcccccccccccccccccccccccccccccccc';
    insertVoicemailEvent($callsid, 44, $recordingSid);
    mockRecordingDeletes([$recordingSid => 404]);

    $this->deleteJson('/api/v1/voicemail/' . $callsid . '?serviceBodyId=44')->assertOk();

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('a Twilio error leaves the voicemail in the list', function () {
    actAsVoicemailAdmin();
    $callsid = 'CA66666666666666666666666666666666';
    $recordingSid = 'REdddddddddddddddddddddddddddddddd';
    insertVoicemailEvent($callsid, 44, $recordingSid);
    mockRecordingDeletes([$recordingSid => 500]);

    $this->deleteJson('/api/v1/voicemail/' . $callsid . '?serviceBodyId=44')
        ->assertStatus(502);

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonPath('data.0.callsid', $callsid);
    expect(EventStatus::where('callsid', $callsid)->count())->toBe(0);
});

test('an unreadable recording url does not hide the voicemail', function () {
    actAsVoicemailAdmin();
    $callsid = 'CA77777777777777777777777777777777';
    insertVoicemailEvent($callsid, 44, null, false, 'https://example.com/not-a-recording');
    $twilio = Mockery::mock(TwilioService::class);
    $twilio->shouldReceive('deleteRecording')->never();
    app()->instance(TwilioService::class, $twilio);

    $this->deleteJson('/api/v1/voicemail/' . $callsid . '?serviceBodyId=44')
        ->assertStatus(422);

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertJsonPath('data.0.callsid', $callsid);
});

test('delete is rejected when the service body is not authorized', function () {
    Sanctum::actingAs(User::factory()->create());
    $callsid = 'CA88888888888888888888888888888888';
    insertVoicemailEvent($callsid, 44, 'REeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee');
    $twilio = Mockery::mock(TwilioService::class);
    $twilio->shouldReceive('deleteRecording')->never();
    app()->instance(TwilioService::class, $twilio);

    $this->deleteJson('/api/v1/voicemail/' . $callsid . '?serviceBodyId=44')
        ->assertStatus(403);

    expect(EventStatus::where('callsid', $callsid)->count())->toBe(0);
});

test('bulk delete removes each selected voicemail recording', function () {
    actAsVoicemailAdmin();
    $first = 'CA99999999999999999999999999999991';
    $second = 'CA99999999999999999999999999999992';
    $firstRecording = 'REffffffffffffffffffffffffffffffff';
    $secondRecording = 'RE00000000000000000000000000000000';
    insertVoicemailEvent($first, 44, $firstRecording);
    insertVoicemailEvent($second, 44, $secondRecording, true);
    mockRecordingDeletes([
        $firstRecording => 200,
        $secondRecording => 200,
    ]);

    $this->postJson('/api/v1/voicemail/delete?serviceBodyId=44', [
        'callsids' => [$first, $second],
    ])->assertOk()->assertJson([
        'deleted' => [$first, $second],
        'failed' => [],
    ]);

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('bulk delete reports a Twilio failure without hiding that voicemail', function () {
    actAsVoicemailAdmin();
    $first = 'CAaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa1';
    $second = 'CAaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa2';
    $firstRecording = 'RE11111111111111111111111111111111';
    $secondRecording = 'RE22222222222222222222222222222222';
    insertVoicemailEvent($first, 44, $firstRecording);
    insertVoicemailEvent($second, 44, $secondRecording);
    mockRecordingDeletes([
        $firstRecording => 200,
        $secondRecording => 500,
    ]);

    $this->postJson('/api/v1/voicemail/delete?serviceBodyId=44', [
        'callsids' => [$first, $second],
    ])->assertOk()->assertJson([
        'deleted' => [$first],
        'failed' => [[
            'callsid' => $second,
            'message' => 'Could not delete the Twilio recording',
        ]],
    ]);

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.callsid', $second);
});

test('bulk delete does not remove a voicemail from another service body', function () {
    actAsVoicemailAdmin();
    $allowed = 'CAbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb1';
    $blocked = 'CAbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb2';
    $allowedRecording = 'RE33333333333333333333333333333333';
    insertVoicemailEvent($allowed, 44, $allowedRecording);
    insertVoicemailEvent($blocked, 55, 'RE44444444444444444444444444444444');
    mockRecordingDeletes([$allowedRecording => 200]);

    $this->postJson('/api/v1/voicemail/delete?serviceBodyId=44', [
        'callsids' => [$allowed, $blocked],
    ])->assertOk()->assertJson([
        'deleted' => [$allowed],
        'failed' => [[
            'callsid' => $blocked,
            'message' => 'Voicemail not found',
        ]],
    ]);

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson('/api/v1/voicemail?serviceBodyId=55')
        ->assertOk()
        ->assertJsonPath('data.0.callsid', $blocked);
});

test('voicemail undelete restores hidden voicemails and leaves other status rows', function () {
    $callsid = 'CAccccccccccccccccccccccccccccccc1';
    insertVoicemailEvent($callsid);
    EventStatus::create([
        'callsid' => $callsid,
        'event_id' => EventId::VOICEMAIL,
        'status' => EventStatusId::VOICEMAIL_DELETED,
    ]);
    EventStatus::create([
        'callsid' => 'CAother',
        'event_id' => EventId::VOLUNTEER_SEARCH,
        'status' => EventStatusId::VOICEMAIL_DELETED,
    ]);

    actAsVoicemailAdmin();
    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->artisan('yap:voicemail-undelete', ['--force' => true])->assertSuccessful();

    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.callsid', $callsid);
    expect(EventStatus::where('event_id', EventId::VOICEMAIL)->count())->toBe(0);
    expect(EventStatus::where('event_id', EventId::VOLUNTEER_SEARCH)->count())->toBe(1);
});

test('voicemail undelete prompts before restoring', function () {
    $callsid = 'CAddddddddddddddddddddddddddddddd1';
    insertVoicemailEvent($callsid);
    EventStatus::create([
        'callsid' => $callsid,
        'event_id' => EventId::VOICEMAIL,
        'status' => EventStatusId::VOICEMAIL_DELETED,
    ]);

    $this->artisan('yap:voicemail-undelete')
        ->expectsConfirmation('Restore 1 hidden voicemail(s) to the admin list?', 'no')
        ->assertSuccessful();

    actAsVoicemailAdmin();
    $this->getJson('/api/v1/voicemail?serviceBodyId=44')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
