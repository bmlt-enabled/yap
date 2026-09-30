<?php

use App\Constants\AuthMechanism;
use App\Constants\EventId;
use App\Constants\EventStatusId;
use App\Models\EventStatus;
use App\Models\Record;
use App\Models\RecordEvent;
use App\Repositories\VoicemailRepository;
use App\Services\TwilioService;
use Carbon\Carbon;
use Twilio\Exceptions\RestException;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

function voicemailAdmin(array $serviceBodyIds = [44]): void
{
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_service_bodies_rights'] = $serviceBodyIds;
}

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

test('voicemail list uses the event callsid when there is no call record', function () {
    $callsid = 'CA11111111111111111111111111111111';
    insertVoicemailEvent($callsid);

    $rows = (new VoicemailRepository())->get(44);

    expect($rows)->toHaveCount(1);
    expect($rows[0]->callsid)->toBe($callsid);
    expect($rows[0]->from_number)->toBeNull();
    expect($rows[0]->to_number)->toBeNull();
    expect($rows[0]->pin)->toBeNull();
});

test('voicemail list keeps caller fields when a call record exists', function () {
    $callsid = 'CA22222222222222222222222222222222';
    insertVoicemailEvent($callsid, 44, 'RE0123456789abcdef0123456789abcdef', true);

    $rows = (new VoicemailRepository())->get(44);

    expect($rows)->toHaveCount(1);
    expect($rows[0]->callsid)->toBe($callsid);
    expect($rows[0]->from_number)->toBe('+15551110000');
    expect($rows[0]->to_number)->toBe('+15552220000');
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
    voicemailAdmin();
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

    $response = $this->post('/api/v1/events/status', [
        'callsid' => $callsid,
        'status' => EventStatusId::VOICEMAIL_DELETED,
        'event_id' => EventId::VOICEMAIL,
    ]);

    $response->assertStatus(201);
    expect((new VoicemailRepository())->get(44))->toHaveCount(0);
    $status = EventStatus::where('callsid', $callsid)->first();
    expect($status->event_id)->toBe(EventId::VOICEMAIL);
    expect($status->status)->toBe(EventStatusId::VOICEMAIL_DELETED);
});

test('deleting a voicemail that has a call record still removes the Twilio recording', function () {
    voicemailAdmin();
    $callsid = 'CA44444444444444444444444444444444';
    $recordingSid = 'REbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    insertVoicemailEvent($callsid, 44, $recordingSid, true);
    mockRecordingDeletes([$recordingSid => 200]);

    $this->post('/api/v1/events/status', [
        'callsid' => $callsid,
        'status' => EventStatusId::VOICEMAIL_DELETED,
        'event_id' => EventId::VOICEMAIL,
    ])->assertStatus(201);

    expect((new VoicemailRepository())->get(44))->toHaveCount(0);
});

test('a Twilio 404 is treated as a successful voicemail delete', function () {
    voicemailAdmin();
    $callsid = 'CA55555555555555555555555555555555';
    $recordingSid = 'REcccccccccccccccccccccccccccccccc';
    insertVoicemailEvent($callsid, 44, $recordingSid);
    mockRecordingDeletes([$recordingSid => 404]);

    $this->post('/api/v1/events/status', [
        'callsid' => $callsid,
        'status' => EventStatusId::VOICEMAIL_DELETED,
        'event_id' => EventId::VOICEMAIL,
    ])->assertStatus(201);

    expect((new VoicemailRepository())->get(44))->toHaveCount(0);
});

test('a Twilio error leaves the voicemail in the list', function () {
    voicemailAdmin();
    $callsid = 'CA66666666666666666666666666666666';
    $recordingSid = 'REdddddddddddddddddddddddddddddddd';
    insertVoicemailEvent($callsid, 44, $recordingSid);
    mockRecordingDeletes([$recordingSid => 500]);

    $this->post('/api/v1/events/status', [
        'callsid' => $callsid,
        'status' => EventStatusId::VOICEMAIL_DELETED,
        'event_id' => EventId::VOICEMAIL,
    ])->assertStatus(502);

    expect((new VoicemailRepository())->get(44))->toHaveCount(1);
    expect(EventStatus::where('callsid', $callsid)->count())->toBe(0);
});

test('an unreadable recording url does not hide the voicemail', function () {
    voicemailAdmin();
    $callsid = 'CA77777777777777777777777777777777';
    insertVoicemailEvent($callsid, 44, null, false, 'https://example.com/not-a-recording');
    $twilio = Mockery::mock(TwilioService::class);
    $twilio->shouldReceive('deleteRecording')->never();
    app()->instance(TwilioService::class, $twilio);

    $this->post('/api/v1/events/status', [
        'callsid' => $callsid,
        'status' => EventStatusId::VOICEMAIL_DELETED,
        'event_id' => EventId::VOICEMAIL,
    ])->assertStatus(422);

    expect((new VoicemailRepository())->get(44))->toHaveCount(1);
});

test('delete is rejected when the service body is not authorized', function () {
    voicemailAdmin([99]);
    $callsid = 'CA88888888888888888888888888888888';
    insertVoicemailEvent($callsid, 44, 'REeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee');
    $twilio = Mockery::mock(TwilioService::class);
    $twilio->shouldReceive('deleteRecording')->never();
    app()->instance(TwilioService::class, $twilio);

    $this->post('/api/v1/events/status', [
        'callsid' => $callsid,
        'status' => EventStatusId::VOICEMAIL_DELETED,
        'event_id' => EventId::VOICEMAIL,
    ])->assertStatus(403);

    expect((new VoicemailRepository())->get(44))->toHaveCount(1);
});

test('bulk delete removes each selected voicemail recording', function () {
    voicemailAdmin();
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

    $this->post('/api/v1/voicemail/delete', [
        'callsids' => [$first, $second],
    ])->assertStatus(200)->assertJson([
        'deleted' => [$first, $second],
        'failed' => [],
    ]);

    expect((new VoicemailRepository())->get(44))->toHaveCount(0);
});

test('bulk delete reports a Twilio failure without hiding that voicemail', function () {
    voicemailAdmin();
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

    $response = $this->post('/api/v1/voicemail/delete', [
        'callsids' => [$first, $second],
    ]);

    $response->assertStatus(200)->assertJson([
        'deleted' => [$first],
        'failed' => [[
            'callsid' => $second,
            'error' => 'Could not delete the Twilio recording',
        ]],
    ]);
    $remaining = (new VoicemailRepository())->get(44);
    expect($remaining)->toHaveCount(1);
    expect($remaining[0]->callsid)->toBe($second);
});

test('bulk delete skips voicemails the caller cannot manage', function () {
    voicemailAdmin([44]);
    $allowed = 'CAbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb1';
    $blocked = 'CAbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb2';
    $allowedRecording = 'RE33333333333333333333333333333333';
    insertVoicemailEvent($allowed, 44, $allowedRecording);
    insertVoicemailEvent($blocked, 55, 'RE44444444444444444444444444444444');
    mockRecordingDeletes([$allowedRecording => 200]);

    $this->post('/api/v1/voicemail/delete', [
        'callsids' => [$allowed, $blocked],
    ])->assertStatus(200)->assertJson([
        'deleted' => [$allowed],
        'failed' => [[
            'callsid' => $blocked,
            'error' => 'Unauthorized',
        ]],
    ]);

    expect((new VoicemailRepository())->get(55))->toHaveCount(1);
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
    expect((new VoicemailRepository())->get(44))->toHaveCount(0);

    $this->artisan('yap:voicemail-undelete', ['--force' => true])->assertSuccessful();

    expect((new VoicemailRepository())->get(44))->toHaveCount(1);
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

    expect((new VoicemailRepository())->get(44))->toHaveCount(0);
});
