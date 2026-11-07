<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Services\SettingsService;
use App\Structures\ServiceBodyCallHandling;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\CallScenario;
use Tests\FakeHttp;

/*
 * The settings that change how a call or text is routed, each run through the
 * core scenarios - meeting search by phone, meeting search by SMS, and a
 * helpline call that dials volunteers - asserting what the caller would notice:
 * which meetings are read out, what the texts say, which volunteers ring, or,
 * for a setting BMLT applies, what yap asks BMLT for.
 *
 * Every BMLT and Google request is served from tests/Fixtures/http.
 */

const RSM_CALLER = '+17325551212';
const RSM_HELPLINE = '+12125551212';
const RSM_LATITUDE = '42.867970';
const RSM_LONGITUDE = '-76.985573';
// A Monday, so meeting-search-42.867970-day2.json answers the search.
const RSM_TIMESTAMP = '2024-02-19 00:00:00';
const RSM_ROOT_SERVER = 'https://latest.aws.bmlt.app/main_server';
const RSM_TOMATO = 'https://aggregator.bmltenabled.org/main_server';

const RSM_BUFFALO_VOLUNTEER = '(555) 106-0001';
const RSM_GENEVA_VOLUNTEER = '(555) 105-3001';

beforeEach(function () {
    $this->httpInstalled = false;
    session()->flush();
});

/**
 * Install the canned BMLT/Google responses once per test. Laravel keeps the
 * first stub that matches, so a test that overrides one calls this with its
 * overrides before running the scenario, which then leaves them alone.
 */
function rsmHttp(array $overrides = []): void
{
    if (!test()->httpInstalled) {
        FakeHttp::install($overrides);
        test()->httpInstalled = true;
    }
}

// --- scenario drivers --------------------------------------------------------

function rsmScenario(array $settings): CallScenario
{
    rsmHttp();

    // SettingsService is not a container singleton, so bind the instance the
    // scenario configures, or the controllers would each build a fresh one.
    app()->instance(SettingsService::class, new SettingsService());

    // A fixed time zone keeps the Google time zone API out of every search.
    return new CallScenario($settings + ['timezone_default' => ['timeZoneId' => 'America/New_York']]);
}

function rsmMeetingSearchByPhone(array $settings = [], array $params = []): CallScenario
{
    return rsmScenario($settings)->callEndpoint('/meeting-search.php', $params + [
        'Latitude' => RSM_LATITUDE,
        'Longitude' => RSM_LONGITUDE,
        'From' => RSM_CALLER,
        'To' => RSM_HELPLINE,
        'Timestamp' => RSM_TIMESTAMP,
    ]);
}

/**
 * The request the SMS gateway redirects a text to once it has geocoded the
 * body: Twilio replays the message's own parameters, SmsSid included, which is
 * what tells meeting-search.php to text the results rather than say them.
 */
function rsmMeetingSearchBySms(array $settings = []): CallScenario
{
    return rsmMeetingSearchByPhone($settings, ['SmsSid' => 'SM' . bin2hex(random_bytes(16))]);
}

function rsmTexts(CallScenario $scenario): array
{
    return array_column(
        array_filter($scenario->twilio->messages, fn ($m) => $m['to'] === RSM_CALLER),
        'body'
    );
}

/** @return list<string> */
function rsmRequestedUrls(string $containing): array
{
    return Http::recorded()
        ->map(fn ($pair) => urldecode($pair[0]->url()))
        ->filter(fn (string $url) => str_contains($url, $containing))
        ->values()
        ->all();
}

function rsmMeetingSearchUrl(): string
{
    $urls = rsmRequestedUrls('data_field_key=id_bigint');
    expect($urls)->not->toBeEmpty('no BMLT meeting search was made');

    return $urls[0];
}

function rsmVolunteer(string $name, string $phoneNumber): array
{
    $shifts = array_map(fn ($day) => [
        'day' => $day,
        'tz' => 'America/New_York',
        'start_time' => '12:00 AM',
        'end_time' => '11:59 PM',
    ], range(1, 7));

    return [
        'volunteer_name' => $name,
        'volunteer_phone_number' => $phoneNumber,
        'volunteer_gender' => VolunteerGender::UNSPECIFIED,
        'volunteer_responder' => 0,
        'volunteer_notes' => '',
        'volunteer_enabled' => true,
        'volunteer_shift_schedule' => base64_encode(json_encode($shifts)),
    ];
}

/**
 * Buffalo Area (1060) and Finger Lakes Area (1053) both route to volunteers,
 * each with one volunteer, so the number that rings says where the call went.
 */
function rsmSeedHelplines(): void
{
    foreach ([1060 => RSM_BUFFALO_VOLUNTEER, 1053 => RSM_GENEVA_VOLUNTEER] as $serviceBodyId => $volunteer) {
        $callHandling = new ServiceBodyCallHandling();
        $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
        $callHandling->service_body_id = $serviceBodyId;
        $callHandling->volunteer_routing_enabled = true;
        $callHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
        ConfigData::createServiceBodyCallHandling($serviceBodyId, $callHandling);
        ConfigData::createVolunteers($serviceBodyId, [rsmVolunteer("Volunteer {$serviceBodyId}", $volunteer)]);
    }
}

/**
 * A caller asks the helpline for someone near Buffalo, NY. Returns the scenario
 * after the helpline search, before the conference is joined.
 */
function rsmHelplineSearch(array $settings = []): CallScenario
{
    rsmSeedHelplines();

    return rsmScenario($settings)
        ->startCall(RSM_CALLER, RSM_HELPLINE)
        ->callEndpoint('/helpline-search.php', ['Digits' => 'Buffalo, NY', 'SearchType' => '1']);
}

function rsmHelplineDial(array $settings = []): CallScenario
{
    $scenario = rsmHelplineSearch($settings);
    expect($scenario->lastTwiml())->toDialConference();

    return $scenario->joinConference();
}

/**
 * A stand-in for BMLT's meeting search that applies the format filter yap
 * sends, so a test can see which meetings an ignore_formats setting removes.
 */
function rsmBmltHonouringFormatFilters(): Closure
{
    $formatKeys = collect(json_decode(FakeHttp::fixture('bmlt/formats.json')))
        ->mapWithKeys(fn ($format) => [$format->id => $format->key_string]);

    return function (Request $request) use ($formatKeys) {
        $results = json_decode(FakeHttp::fixture('bmlt/meeting-search-42.867970-day2.json'));
        preg_match_all('/formats\[\]=-(\d+)/', urldecode($request->url()), $excluded);
        $excludedKeys = array_map(fn ($id) => $formatKeys[$id], $excluded[1]);

        $results->meetings = array_values(array_filter(
            $results->meetings,
            fn ($meeting) => !array_intersect(explode(',', $meeting->formats), $excludedKeys)
        ));

        return Http::response(json_encode($results));
    };
}

// --- the baseline the matrix compares against ------------------------------

test('baseline: meeting search by phone reads out the five nearest Monday meetings and texts them', function () {
    $scenario = rsmMeetingSearchByPhone();

    expect($scenario->lastTwiml())
        ->toContain('<Say voice="alice" language="en-US">Step up and Be Free</Say>')
        ->toContain('starts at Monday 7:00 PM')
        ->not->toContain('post-call-action.php');
    expect(rsmTexts($scenario))->toHaveCount(5);

    $url = rsmMeetingSearchUrl();
    expect($url)
        ->toStartWith(RSM_ROOT_SERVER . '/client_interface/json/')
        ->toContain('&geo_width=-50&')
        ->not->toContain('formats[]')
        ->not->toContain('advanced_published');
});

test('baseline: meeting search by SMS texts the five nearest Monday meetings and says nothing', function () {
    $scenario = rsmMeetingSearchBySms();

    expect($scenario->lastTwiml())->not->toContain('<Say');
    $texts = rsmTexts($scenario);
    expect($texts)->toHaveCount(5);
    expect($texts[0])->toStartWith('Step up and Be Free Monday 7:00 PM, 128 Main street, Clifton Springs, NY');
});

test('baseline: a helpline call from Buffalo, NY rings the Buffalo Area volunteer', function () {
    $scenario = rsmHelplineDial();

    $scenario->assertDialedInOrder([RSM_BUFFALO_VOLUNTEER]);
    expect(rsmRequestedUrls('service_body_bigint')[0])->toStartWith(RSM_ROOT_SERVER . '/');
});

// --- meeting search, by phone and by SMS -----------------------------------

/*
 * Each case: the settings, then what to expect by phone and by SMS. The
 * closures get the finished scenario.
 */
dataset('meeting search settings', function () {
    return [
        'time_format' => [
            ['time_format' => 'H:i'],
            fn (CallScenario $s) => expect($s->lastTwiml())
                ->toContain('starts at Monday 19:00')
                ->not->toContain('7:00 PM'),
            fn (CallScenario $s) => expect(rsmTexts($s)[0])->toStartWith('Step up and Be Free Monday 19:00,'),
        ],
        'meeting_search_radius' => [
            ['meeting_search_radius' => -10],
            fn () => expect(rsmMeetingSearchUrl())->toContain('&geo_width=-10&')->not->toContain('geo_width=-50'),
            fn () => expect(rsmMeetingSearchUrl())->toContain('&geo_width=-10&'),
        ],
        'include_unpublished' => [
            ['include_unpublished' => 1],
            fn () => expect(rsmMeetingSearchUrl())->toContain('&advanced_published=0'),
            fn () => expect(rsmMeetingSearchUrl())->toContain('&advanced_published=0'),
        ],
        'include_unpublished off' => [
            ['include_unpublished' => 0],
            fn () => expect(rsmMeetingSearchUrl())->not->toContain('advanced_published'),
            fn () => expect(rsmMeetingSearchUrl())->not->toContain('advanced_published'),
        ],
        'tomato_meeting_search' => [
            ['tomato_meeting_search' => true],
            fn () => expect(rsmMeetingSearchUrl())->toStartWith(RSM_TOMATO . '/client_interface/json/'),
            fn () => expect(rsmMeetingSearchUrl())->toStartWith(RSM_TOMATO . '/client_interface/json/'),
        ],
        'tomato_meeting_search with a custom tomato_url' => [
            ['tomato_meeting_search' => 'true', 'tomato_url' => 'https://tomato.example.org/main_server/'],
            fn () => expect(rsmMeetingSearchUrl())->toStartWith('https://tomato.example.org/main_server/client_interface/'),
            fn () => expect(rsmMeetingSearchUrl())->toStartWith('https://tomato.example.org/main_server/client_interface/'),
        ],
        'mobile_check, caller on a landline' => [
            ['mobile_check' => true, '_carrier' => 'landline'],
            // still read out, but nothing is texted to a landline
            function (CallScenario $s) {
                expect($s->lastTwiml())->toContain('Step up and Be Free');
                expect(rsmTexts($s))->toBe([]);
            },
            fn (CallScenario $s) => expect(rsmTexts($s))->toBe([]),
        ],
        'mobile_check, caller on a mobile' => [
            ['mobile_check' => true, '_carrier' => 'mobile'],
            fn (CallScenario $s) => expect(rsmTexts($s))->toHaveCount(5),
            fn (CallScenario $s) => expect(rsmTexts($s))->toHaveCount(5),
        ],
        'infinite_searching' => [
            ['infinite_searching' => true],
            fn (CallScenario $s) => expect($s->lastTwiml())
                ->toContain('<Gather numDigits="1" timeout="10" speechTimeout="auto" action="post-call-action.php" method="GET">')
                ->toContain('press two if you would like to search again'),
            // a text conversation has no keypad to press two on
            fn (CallScenario $s) => expect($s->lastTwiml())->not->toContain('<Gather'),
        ],
    ];
});

/** Pull the test-only _carrier key out of a case's settings. */
function rsmApplyCarrier(array &$settings): ?string
{
    $carrier = $settings['_carrier'] ?? null;
    unset($settings['_carrier']);

    return $carrier;
}

test('meeting search by phone honours the setting', function (array $settings, Closure $byPhone, Closure $bySms) {
    $carrier = rsmApplyCarrier($settings);
    $scenario = rsmScenario($settings);
    if ($carrier) {
        $scenario->twilio->setPhoneLookup(RSM_CALLER, (object) ['carrier' => ['type' => $carrier]]);
    }

    $scenario->callEndpoint('/meeting-search.php', [
        'Latitude' => RSM_LATITUDE,
        'Longitude' => RSM_LONGITUDE,
        'From' => RSM_CALLER,
        'To' => RSM_HELPLINE,
        'Timestamp' => RSM_TIMESTAMP,
    ]);

    $byPhone($scenario);
})->with('meeting search settings');

test('meeting search by SMS honours the setting', function (array $settings, Closure $byPhone, Closure $bySms) {
    $carrier = rsmApplyCarrier($settings);
    $scenario = rsmScenario($settings);
    if ($carrier) {
        $scenario->twilio->setPhoneLookup(RSM_CALLER, (object) ['carrier' => ['type' => $carrier]]);
    }

    $scenario->callEndpoint('/meeting-search.php', [
        'Latitude' => RSM_LATITUDE,
        'Longitude' => RSM_LONGITUDE,
        'From' => RSM_CALLER,
        'To' => RSM_HELPLINE,
        'Timestamp' => RSM_TIMESTAMP,
        'SmsSid' => 'SM' . bin2hex(random_bytes(16)),
    ]);

    $bySms($scenario);
})->with('meeting search settings');

test('ignore_formats leaves meetings with those formats out of the search', function (bool $bySms) {
    rsmHttp(['*switcher=GetSearchResults*data_field_key=id_bigint*' => rsmBmltHonouringFormatFilters()]);

    // "Step up and Be Free" and "Ties That Bind Us Together" are Open (O) meetings.
    $scenario = $bySms
        ? rsmMeetingSearchBySms(['ignore_formats' => 'O,BT'])
        : rsmMeetingSearchByPhone(['ignore_formats' => 'O,BT']);

    // formats.json: O is 17, BT is 3
    expect(rsmMeetingSearchUrl())->toContain('&formats[]=-17&formats[]=-3');

    $heard = $bySms ? implode("\n", rsmTexts($scenario)) : $scenario->lastTwiml();
    expect($heard)
        ->not->toContain('Step up and Be Free')
        ->not->toContain('Ties That Bind Us Together')
        ->toContain('A New Way of Life');
})->with(['by phone' => false, 'by SMS' => true]);

test('without ignore_formats the same search includes those meetings', function () {
    rsmHttp(['*switcher=GetSearchResults*data_field_key=id_bigint*' => rsmBmltHonouringFormatFilters()]);

    expect(rsmMeetingSearchByPhone()->lastTwiml())
        ->toContain('Step up and Be Free')
        ->toContain('Ties That Bind Us Together');
});

/*
 * meeting_result_sort only matters when the results span two days, which they
 * do when today has fewer meetings than result_count_max and the search runs
 * again for tomorrow. Two Monday meetings make that happen here.
 */
function rsmTwoMondayMeetings(): void
{
    $monday = json_decode(FakeHttp::fixture('bmlt/meeting-search-42.867970-day2.json'));
    $monday->meetings = array_slice($monday->meetings, 0, 2);
    rsmHttp([
        '*switcher=GetSearchResults*data_field_key=id_bigint*weekdays=2*' => Http::response(json_encode($monday)),
    ]);
}

test('meeting_result_sort picks which day is read out first', function (array $settings, string $first, bool $bySms) {
    rsmTwoMondayMeetings();

    $scenario = $bySms ? rsmMeetingSearchBySms($settings) : rsmMeetingSearchByPhone($settings);

    if ($bySms) {
        expect(rsmTexts($scenario)[0])->toStartWith($first);
    } else {
        expect($scenario->lastTwiml())
            ->toContain("<Say voice=\"alice\" language=\"en-US\">number 1</Say><Say voice=\"alice\" language=\"en-US\">{$first}</Say>");
    }
})->with([
    'today first (the default)' => [[], 'Step up and Be Free'],
    'Tuesday first' => [['meeting_result_sort' => 3], 'New Hope'],
])->with(['by phone' => false, 'by SMS' => true]);

/*
 * grace_minutes keeps a meeting that started a few minutes ago in the results.
 * It applies to a live search - one with no Timestamp - which runs on the real
 * clock, so the meeting is placed ten minutes before now, in a time zone where
 * now is around midday so that never crosses midnight.
 */
function rsmMeetingThatStartedTenMinutesAgo(): string
{
    $offset = 12 - (int) gmdate('G');
    $timeZone = sprintf('Etc/GMT%+d', -$offset);
    $started = new DateTime('-10 minutes', new DateTimeZone($timeZone));

    $results = json_decode(FakeHttp::fixture('bmlt/meeting-search-42.867970-day2.json'));
    $meeting = clone $results->meetings[0];
    $meeting->meeting_name = 'Started Ten Minutes Ago';
    $meeting->weekday_tinyint = (string) ((int) $started->format('w') + 1);
    $meeting->start_time = $started->format('H:i:00');
    $meeting->formats = '';
    $results->meetings = [$meeting];
    rsmHttp(['*switcher=GetSearchResults*data_field_key=id_bigint*' => Http::response(json_encode($results))]);

    return $timeZone;
}

test('grace_minutes decides whether a meeting that just started is still offered', function (int $graceMinutes, bool $offered, bool $bySms) {
    $timeZone = rsmMeetingThatStartedTenMinutesAgo();
    $settings = ['grace_minutes' => $graceMinutes, 'timezone_default' => ['timeZoneId' => $timeZone]];
    $params = [
        'Latitude' => RSM_LATITUDE,
        'Longitude' => RSM_LONGITUDE,
        'From' => RSM_CALLER,
        'To' => RSM_HELPLINE,
    ] + ($bySms ? ['SmsSid' => 'SM' . bin2hex(random_bytes(16))] : []);

    $scenario = rsmScenario($settings)->callEndpoint('/meeting-search.php', $params);

    $heard = $bySms ? implode("\n", rsmTexts($scenario)) : $scenario->lastTwiml();
    if ($offered) {
        expect($heard)->toContain('Started Ten Minutes Ago');
    } else {
        expect($heard)->not->toContain('Started Ten Minutes Ago');
    }
})->with([
    'the default 15 minutes' => [15, true],
    '5 minutes' => [5, false],
    '0 minutes' => [0, false],
    '60 minutes' => [60, true],
])->with(['by phone' => false, 'by SMS' => true]);

// --- location lookups ------------------------------------------------------

test('location_lookup_bias is the region hint sent to the geocoder for a texted location', function (?string $bias, string $components) {
    $settings = $bias === null ? [] : ['location_lookup_bias' => $bias];

    rsmScenario($settings)->callEndpoint('/sms-gateway.php', [
        'SmsSid' => 'SM' . bin2hex(random_bytes(16)),
        'From' => RSM_CALLER,
        'To' => RSM_HELPLINE,
        'Body' => 'Geneva, NY',
    ])->expectCallRedirect('meeting-search.php?SearchType=1&Latitude=42.8690271&Longitude=-76.9786122');

    $geocodes = rsmRequestedUrls('/geocode/json');
    expect($geocodes)->toHaveCount(1);
    expect($geocodes[0])->toContain('address=Geneva, NY&components=' . $components);
})->with([
    'the default' => [null, 'country:us'],
    'Canada' => ['country:ca', 'country:ca'],
    'a region and a country' => ['administrative_area:ON|country:CA', 'administrative_area:ON|country:CA'],
]);

test('sms_bias_bypass drops the province hint from a spoken location', function (?bool $bypass, string $digits) {
    $settings = ['toll_province_bias' => 'NY'] + ($bypass === null ? [] : ['sms_bias_bypass' => $bypass]);

    rsmScenario($settings)->callEndpoint('/voice-input-result.php', [
        'SpeechResult' => 'Geneva',
        'SearchType' => '2',
    ])->expectCallRedirect("address-lookup.php?Digits={$digits}&SearchType=2");
})->with([
    'off (the default)' => [null, 'Geneva%2C+NY'],
    'off' => [false, 'Geneva%2C+NY'],
    'on' => [true, 'Geneva%2C+'],
]);

test('a texted location is never given a province hint, so sms_bias_bypass has nothing to bypass by SMS', function (bool $bypass) {
    // CallFlowController::geocodeAddress() only appends the province when the
    // body matches neither ',' nor /[0-9]*/ - and /[0-9]*/ matches every string.
    rsmScenario(['toll_province_bias' => 'NY', 'sms_bias_bypass' => $bypass])->callEndpoint('/sms-gateway.php', [
        'SmsSid' => 'SM' . bin2hex(random_bytes(16)),
        'From' => RSM_CALLER,
        'To' => RSM_HELPLINE,
        'Body' => 'Geneva',
    ]);

    expect(rsmRequestedUrls('/geocode/json')[0])->toContain('address=Geneva&components=');
})->with(['bypass off' => false, 'bypass on' => true]);

// --- helpline volunteer dial -----------------------------------------------

test('call_routing_filter narrows the helpline search, which changes the volunteer who rings', function () {
    // BMLT applies the filter; here it answers a search filtered to the Finger
    // Lakes Area as if that were the only service body near the caller.
    rsmHttp([
        '*service_body_bigint*services=1053*' => Http::response(FakeHttp::fixture('bmlt/helpline-search-geneva-ny.json')),
    ]);

    $scenario = rsmHelplineDial(['call_routing_filter' => '&services=1053']);

    expect(rsmRequestedUrls('service_body_bigint')[0])->toEndWith('&geo_width=30&services=1053');
    $scenario->assertDialedInOrder([RSM_GENEVA_VOLUNTEER]);
});

test('helpline_search_radius is how far helpline routing looks for a service body', function () {
    // within 5 miles of the caller, BMLT finds nothing
    rsmHttp([
        '*service_body_bigint*geo_width=5' => Http::response(FakeHttp::fixture('bmlt/helpline-search-empty.json')),
    ]);

    $scenario = rsmHelplineSearch(['helpline_search_radius' => 5]);

    expect(rsmRequestedUrls('service_body_bigint')[0])->toEndWith('&geo_width=5');
    expect($scenario->lastTwiml())
        ->toContain('RetryMessage=Couldn%27t+find+service+body+coverage+for+that+location.')
        ->not->toContain('<Conference');
});

test('helpline_bmlt_root_server is where helpline routing looks up the caller', function () {
    $helplineServer = 'https://helpline.example.org/main_server';
    rsmHttp([
        // a different server can know different service bodies
        $helplineServer . '/*service_body_bigint*' => Http::response(FakeHttp::fixture('bmlt/helpline-search-geneva-ny.json')),
    ]);

    $scenario = rsmHelplineDial(['helpline_bmlt_root_server' => $helplineServer . '/']);

    expect(rsmRequestedUrls('service_body_bigint'))->each->toStartWith($helplineServer . '/client_interface/json/');
    expect(rsmRequestedUrls('switcher=GetServiceBodies'))->each->toStartWith($helplineServer . '/client_interface/json/');
    $scenario->assertDialedInOrder([RSM_GENEVA_VOLUNTEER]);
});

test('helpline_bmlt_root_server does not move the meeting search', function () {
    rsmMeetingSearchByPhone(['helpline_bmlt_root_server' => 'https://helpline.example.org/main_server']);

    expect(rsmMeetingSearchUrl())->toStartWith(RSM_ROOT_SERVER . '/');
});

function rsmTomatoSaysTheCallerIsServedBy(string $rootServerUri): void
{
    rsmHttp([
        RSM_TOMATO . '/*data_field_key=root_server_uri*' => Http::response(json_encode([
            ['root_server_uri' => $rootServerUri],
        ])),
    ]);
}

test('tomato_helpline_routing keeps a caller this root server covers on its own volunteers', function () {
    rsmTomatoSaysTheCallerIsServedBy(RSM_ROOT_SERVER);

    $scenario = rsmHelplineDial(['tomato_helpline_routing' => true]);

    expect(rsmRequestedUrls('data_field_key=root_server_uri'))->not->toBeEmpty();
    expect(rsmRequestedUrls('service_body_bigint')[0])->toStartWith(RSM_ROOT_SERVER . '/');
    $scenario->assertDialedInOrder([RSM_BUFFALO_VOLUNTEER]);
});

test('tomato_helpline_routing sends a caller another root server covers to that service body\'s helpline', function () {
    rsmTomatoSaysTheCallerIsServedBy('https://elsewhere.example.org/main_server/');

    $scenario = rsmHelplineSearch(['tomato_helpline_routing' => true]);

    // routed through the aggregator, not this server's volunteers
    expect(rsmRequestedUrls('service_body_bigint')[0])->toStartWith(RSM_TOMATO . '/');
    expect($scenario->lastTwiml())->not->toContain('<Conference');
    expect($scenario->twilio->dialedNumbers())->toBe([]);
});

test('without tomato_helpline_routing the aggregator is never asked', function () {
    rsmHelplineDial(['tomato_helpline_routing' => false])->assertDialedInOrder([RSM_BUFFALO_VOLUNTEER]);

    expect(rsmRequestedUrls(RSM_TOMATO))->toBe([]);
});

// --- settings with no server-side effect -----------------------------------

test('phone_number_validation reaches the volunteers page through the settings API', function () {
    // The admin volunteers page reads this from GET /api/v1/settings and uses
    // it as the default country when it validates volunteer numbers.
    \Laravel\Sanctum\Sanctum::actingAs(\App\Models\User::factory()->admin()->create());
    $settings = new SettingsService();
    $settings->set('phone_number_validation', 'GB');
    app()->instance(SettingsService::class, $settings);

    $setting = collect($this->getJson('/api/v1/settings')->assertStatus(200)->json('settings'))
        ->firstWhere('key', 'phone_number_validation');

    expect($setting)->toMatchArray(['key' => 'phone_number_validation', 'value' => 'GB', 'default' => 'US']);
});
