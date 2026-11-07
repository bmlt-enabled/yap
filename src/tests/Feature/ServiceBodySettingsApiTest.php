<?php

use App\Constants\DataType;
use App\Models\ConfigData;
use App\Models\User;
use App\Services\SettingsService;
use App\Structures\Settings;
use App\Utilities\VolunteerScheduleHelpers;
use Laravel\Sanctum\Sanctum;
use Tests\CallScenario;
use Tests\FakeHttp;

// Per-service-body settings (new in 5.0) are applied to a call by CallSession,
// so they drive that service body's call routing. In tests/Fixtures/http/bmlt
// service body 1060 (Buffalo Area) is a child of 1059 (Western New York Region),
// and 1053 (Finger Lakes Area) belongs to another region.

const SB_AREA = 1060;
const SB_REGION = 1059;
const SB_OTHER = 1053;

beforeEach(function () {
    FakeHttp::install();
});

function actingAsTopLevelAdmin(): User
{
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    return $user;
}

function actingAsServiceBodyAdmin(int ...$serviceBodyIds): User
{
    $user = User::factory()->create(['service_bodies' => implode(',', $serviceBodyIds)]);
    Sanctum::actingAs($user);

    return $user;
}

function saveServiceBodySettings(int|string $serviceBodyId, array $fields)
{
    return test()->postJson("/api/v1/settings/serviceBody/{$serviceBodyId}", ['fields' => $fields]);
}

function storedServiceBodySettings(int $serviceBodyId): ?array
{
    $row = ConfigData::where('service_body_id', $serviceBodyId)
        ->where('data_type', DataType::YAP_CONFIG_V2)
        ->get();
    expect($row->count())->toBeLessThanOrEqual(1);

    return $row->isEmpty() ? null : json_decode($row->first()->data, true)[0];
}

// --- reading ---------------------------------------------------------------

test('a service body with no overrides reads back as an empty list', function () {
    actingAsTopLevelAdmin();

    $this->getJson('/api/v1/settings/serviceBody/' . SB_AREA)
        ->assertStatus(200)
        ->assertExactJson([]);
});

test('saved overrides read back as setting/value pairs', function () {
    actingAsTopLevelAdmin();
    ConfigData::createServiceBodyConfiguration(SB_AREA, new Settings((object) [
        'title' => 'Buffalo Area Helpline',
        'jft_option' => 'true',
    ]));

    $this->getJson('/api/v1/settings/serviceBody/' . SB_AREA)
        ->assertStatus(200)
        ->assertExactJson([
            ['setting' => 'title', 'value' => 'Buffalo Area Helpline'],
            ['setting' => 'jft_option', 'value' => 'true'],
        ]);
});

test('a stored row with no data reads back as an empty list', function () {
    actingAsTopLevelAdmin();
    ConfigData::create([
        'service_body_id' => SB_AREA,
        'parent_id' => 0,
        'data' => 'null',
        'data_type' => DataType::YAP_CONFIG_V2,
    ]);

    $this->getJson('/api/v1/settings/serviceBody/' . SB_AREA)
        ->assertStatus(200)
        ->assertExactJson([]);
});

test('overrides stored as a bare object rather than a one-element array still read back', function () {
    actingAsTopLevelAdmin();
    ConfigData::create([
        'service_body_id' => SB_AREA,
        'parent_id' => 0,
        'data' => json_encode(['title' => 'Bare']),
        'data_type' => DataType::YAP_CONFIG_V2,
    ]);

    $this->getJson('/api/v1/settings/serviceBody/' . SB_AREA)
        ->assertStatus(200)
        ->assertExactJson([['setting' => 'title', 'value' => 'Bare']]);
});

// --- saving ----------------------------------------------------------------

test('saving creates the overrides, then replaces them', function () {
    actingAsTopLevelAdmin();

    saveServiceBodySettings(SB_AREA, [
        ['setting' => 'title', 'value' => 'First'],
        ['setting' => 'jft_option', 'value' => 'true'],
    ])->assertStatus(200);
    expect(storedServiceBodySettings(SB_AREA))->toBe(['title' => 'First', 'jft_option' => 'true']);

    saveServiceBodySettings(SB_AREA, [
        ['setting' => 'title', 'value' => 'Second'],
    ])->assertStatus(200);
    expect(storedServiceBodySettings(SB_AREA))->toBe(['title' => 'Second']);

    saveServiceBodySettings(SB_AREA, [])->assertStatus(200);
    expect(storedServiceBodySettings(SB_AREA))->toBe([]);
});

test('saving keeps non-string scalar values as sent', function () {
    actingAsTopLevelAdmin();

    saveServiceBodySettings(SB_AREA, [
        ['setting' => 'result_count_max', 'value' => 3],
        ['setting' => 'sms_ask', 'value' => true],
        ['setting' => 'fallback_number', 'value' => null],
    ])->assertStatus(200);

    expect(storedServiceBodySettings(SB_AREA))->toBe([
        'result_count_max' => 3,
        'sms_ask' => true,
        'fallback_number' => null,
    ]);
});

test('saving answers with the system settings', function () {
    actingAsTopLevelAdmin();

    saveServiceBodySettings(SB_AREA, [['setting' => 'title', 'value' => 'x']])
        ->assertStatus(200)
        ->assertJsonStructure(['languageSelections', 'settings' => [['default', 'docs', 'key', 'source', 'value']]]);
});

test('saving one service body leaves the others alone', function () {
    actingAsTopLevelAdmin();
    saveServiceBodySettings(SB_REGION, [['setting' => 'title', 'value' => 'Region']])->assertStatus(200);

    saveServiceBodySettings(SB_AREA, [['setting' => 'title', 'value' => 'Area']])->assertStatus(200);

    expect(storedServiceBodySettings(SB_REGION))->toBe(['title' => 'Region']);
    expect(storedServiceBodySettings(SB_AREA))->toBe(['title' => 'Area']);
});

// --- permissions -----------------------------------------------------------

test('a top-level admin can read and save any service body', function (int $serviceBodyId) {
    actingAsTopLevelAdmin();

    saveServiceBodySettings($serviceBodyId, [['setting' => 'title', 'value' => 'ok']])->assertStatus(200);
    $this->getJson("/api/v1/settings/serviceBody/{$serviceBodyId}")
        ->assertStatus(200)
        ->assertExactJson([['setting' => 'title', 'value' => 'ok']]);
})->with([SB_AREA, SB_REGION, SB_OTHER]);

test('a service body admin can read and save their own service body', function () {
    actingAsServiceBodyAdmin(SB_AREA);

    saveServiceBodySettings(SB_AREA, [['setting' => 'title', 'value' => 'mine']])->assertStatus(200);
    $this->getJson('/api/v1/settings/serviceBody/' . SB_AREA)
        ->assertStatus(200)
        ->assertExactJson([['setting' => 'title', 'value' => 'mine']]);
});

test('a service body admin cannot read another service body', function (int $serviceBodyId) {
    ConfigData::createServiceBodyConfiguration($serviceBodyId, new Settings((object) ['title' => 'secret']));
    actingAsServiceBodyAdmin(SB_AREA);

    $this->getJson("/api/v1/settings/serviceBody/{$serviceBodyId}")
        ->assertStatus(403)
        ->assertJsonMissing(['value' => 'secret']);
})->with([
    'a different region' => SB_OTHER,
    // administering a child does not grant its parent
    'its own parent' => SB_REGION,
]);

test('a service body admin cannot save another service body', function (int $serviceBodyId) {
    ConfigData::createServiceBodyConfiguration($serviceBodyId, new Settings((object) ['title' => 'theirs']));
    actingAsServiceBodyAdmin(SB_AREA);

    saveServiceBodySettings($serviceBodyId, [['setting' => 'title', 'value' => 'hijacked']])
        ->assertStatus(403);

    expect(storedServiceBodySettings($serviceBodyId))->toBe(['title' => 'theirs']);
})->with([
    'a different region' => SB_OTHER,
    'its own parent' => SB_REGION,
]);

test('a user with no service bodies cannot read or save any', function () {
    actingAsServiceBodyAdmin();

    $this->getJson('/api/v1/settings/serviceBody/' . SB_AREA)->assertStatus(403);
    saveServiceBodySettings(SB_AREA, [['setting' => 'title', 'value' => 'x']])->assertStatus(403);
    expect(storedServiceBodySettings(SB_AREA))->toBeNull();
});

test('the service body settings endpoints need a login', function () {
    $this->getJson('/api/v1/settings/serviceBody/' . SB_AREA)->assertStatus(401);
    saveServiceBodySettings(SB_AREA, [['setting' => 'title', 'value' => 'x']])->assertStatus(401);
    expect(storedServiceBodySettings(SB_AREA))->toBeNull();
});

// --- validation ------------------------------------------------------------

test('a malformed service body id is rejected', function (string $serviceBodyId) {
    actingAsTopLevelAdmin();

    $this->getJson("/api/v1/settings/serviceBody/{$serviceBodyId}")->assertStatus(400);
    saveServiceBodySettings($serviceBodyId, [['setting' => 'title', 'value' => 'x']])->assertStatus(400);
})->with(['0', 'abc', '12abc', '-5', '1.5']);

test('a body without a fields array is rejected and nothing is stored', function (string $body) {
    actingAsTopLevelAdmin();

    $this->call('POST', '/api/v1/settings/serviceBody/' . SB_AREA, content: $body)
        ->assertStatus(422)
        ->assertJsonPath('errors.0', 'Expected a JSON object with a "fields" array.');

    expect(storedServiceBodySettings(SB_AREA))->toBeNull();
})->with([
    'empty' => '',
    'not json' => 'title=x',
    'no fields' => '{}',
    'fields is an object' => '{"fields": {"setting": "title", "value": "x"}}',
    'fields is a string' => '{"fields": "title"}',
]);

test('invalid fields are rejected and the stored overrides are kept', function (array $field, string $error) {
    actingAsTopLevelAdmin();
    ConfigData::createServiceBodyConfiguration(SB_AREA, new Settings((object) ['title' => 'kept']));

    saveServiceBodySettings(SB_AREA, [['setting' => 'jft_option', 'value' => 'true'], $field])
        ->assertStatus(422)
        ->assertJson(['message' => 'Invalid settings', 'errors' => [$error]]);

    expect(storedServiceBodySettings(SB_AREA))->toBe(['title' => 'kept']);
})->with([
    'unknown setting' => [
        ['setting' => 'not_a_setting', 'value' => 'x'],
        'fields[1]: "not_a_setting" is not a setting.',
    ],
    'no setting' => [
        ['value' => 'x'],
        'fields[1] must have a string "setting" and a "value".',
    ],
    'no value' => [
        ['setting' => 'title'],
        'fields[1] must have a string "setting" and a "value".',
    ],
    'setting is not a string' => [
        ['setting' => 5, 'value' => 'x'],
        'fields[1] must have a string "setting" and a "value".',
    ],
    'field is not an object' => [
        ['title'],
        'fields[1] must have a string "setting" and a "value".',
    ],
    'value is an array' => [
        ['setting' => 'title', 'value' => ['a', 'b']],
        'fields[1]: the value of "title" must be a string, number, boolean or null.',
    ],
    'value is an object' => [
        ['setting' => 'title', 'value' => ['a' => 'b']],
        'fields[1]: the value of "title" must be a string, number, boolean or null.',
    ],
]);

test('every invalid field is reported at once', function () {
    actingAsTopLevelAdmin();

    saveServiceBodySettings(SB_AREA, [
        ['setting' => 'nope', 'value' => 'x'],
        ['setting' => 'title', 'value' => 'fine'],
        ['setting' => 'also_nope', 'value' => 'y'],
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors', [
            'fields[0]: "nope" is not a setting.',
            'fields[2]: "also_nope" is not a setting.',
        ]);
});

test('the per-language prompt and voice settings are accepted', function () {
    actingAsTopLevelAdmin();

    saveServiceBodySettings(SB_AREA, [
        ['setting' => 'en_US_greeting', 'value' => 'https://example.org/greeting.mp3'],
        ['setting' => 'es_US_voice', 'value' => 'Polly.Lupe'],
    ])->assertStatus(200);

    expect(storedServiceBodySettings(SB_AREA))->toBe([
        'en_US_greeting' => 'https://example.org/greeting.mp3',
        'es_US_voice' => 'Polly.Lupe',
    ]);
});

// --- the saved override reaches the next call -------------------------------

// One call per test: the router keeps the controller instances it built, so a
// second CallScenario in the same test would talk to the first one's Twilio fake.
function startCallFor(int $serviceBodyId): CallScenario
{
    // The caller's session is a fresh one, as it would be for a real call.
    session()->flush();

    return (new CallScenario())
        ->withCallData(['override_service_body_id' => (string) $serviceBodyId])
        ->startCall('+17165551212', '+17165550000');
}

test('without a saved override the menu has no reading options', function () {
    $twiml = startCallFor(SB_AREA)->lastTwiml();

    expect($twiml)->toContain('press one')
        ->not->toContain('just for today')
        ->not->toContain('spiritual principle a day');
});

test('a saved override changes the menu on the next call to that service body', function () {
    actingAsServiceBodyAdmin(SB_AREA);
    saveServiceBodySettings(SB_AREA, [['setting' => 'jft_option', 'value' => 'true']])->assertStatus(200);

    expect(startCallFor(SB_AREA)->lastTwiml())
        ->toContain('press three to listen to the just for today');
});

test('a saved override reaches calls to child service bodies, and the child wins', function () {
    actingAsTopLevelAdmin();
    saveServiceBodySettings(SB_REGION, [
        ['setting' => 'jft_option', 'value' => 'true'],
        ['setting' => 'spad_option', 'value' => 'true'],
    ])->assertStatus(200);
    saveServiceBodySettings(SB_AREA, [['setting' => 'spad_option', 'value' => 'false']])->assertStatus(200);

    $twiml = startCallFor(SB_AREA)->lastTwiml();

    expect($twiml)->toContain('press three to listen to the just for today');
    expect($twiml)->not->toContain('spiritual principle a day');
});

test('a saved override does not leak into calls to another service body', function () {
    actingAsTopLevelAdmin();
    saveServiceBodySettings(SB_AREA, [['setting' => 'jft_option', 'value' => 'true']])->assertStatus(200);

    expect(startCallFor(SB_OTHER)->lastTwiml())->not->toContain('just for today');
});

// --- the other settings endpoints -------------------------------------------

test('the allowlist lists every visible setting with its default and docs link', function () {
    actingAsServiceBodyAdmin(SB_AREA);
    $settings = new SettingsService();

    $response = $this->getJson('/api/v1/settings/allowlist')->assertStatus(200);

    $visible = collect($settings->allowlist())->reject(fn ($meta) => $meta['hidden']);
    expect(collect($response->json())->pluck('setting')->all())->toBe($visible->keys()->all());

    $byName = collect($response->json())->keyBy('setting');
    expect($byName['jft_option'])->toBe([
        'setting' => 'jft_option',
        'default' => false,
        'description' => 'https://yap.bmlt.app/miscellaneous/playback-for-readings',
    ]);
    // hidden settings, such as the Twilio credentials, are never offered
    expect($byName->keys())->not->toContain('twilio_auth_token')->not->toContain('docs_base');
    // a setting with no docs page has an empty description, not a bare docs_base
    expect($byName['en_US_greeting']['description'])->toBe('');
});

test('the timezone list is every PHP timezone', function () {
    actingAsServiceBodyAdmin(SB_AREA);

    $this->getJson('/api/v1/settings/timezones')
        ->assertStatus(200)
        ->assertExactJson(VolunteerScheduleHelpers::getTimezoneList());

    expect($this->getJson('/api/v1/settings/timezones')->json())
        ->toContain('America/New_York')
        ->toContain('UTC');
});

test('the allowlist and timezones need a login', function (string $path) {
    $this->getJson($path)->assertStatus(401);
})->with(['/api/v1/settings/allowlist', '/api/v1/settings/timezones']);
