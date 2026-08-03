<?php

use App\Constants\CycleAlgorithm;
use App\Constants\SpecialPhoneNumber;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Constants\VolunteerType;
use App\Models\ConfigData;
use App\Services\SettingsService;
use App\Services\TwilioService;
use App\Services\VolunteerService;
use App\Structures\ServiceBodyCallHandling;
use App\Structures\VolunteerRoutingParameters;
use Tests\FakeTwilioHttpClient;

/**
 * Regression coverage for https://github.com/bmlt-enabled/yap/issues/1578.
 *
 * GenderRoutingTest asserts the TwiML of the gender prompts, which is byte-identical
 * whether routing works or not -- only the volunteer that ends up being dialed changes.
 * These tests assert the routing *decision* instead: which phone number comes back out
 * of the volunteer filters in VolunteerRoutingHelpers.
 */

const ROUTING_UNSPECIFIED_NUMBER = "(555) 111-0000";
const ROUTING_MALE_NUMBER = "(555) 111-0001";
const ROUTING_FEMALE_NUMBER = "(555) 111-0002";
const ROUTING_SHIFT_TZ = "America/New_York";

/**
 * An all-week, all-day shift schedule, so that only the filter under test can exclude
 * a volunteer. Pass $type to exercise checkVolunteerRoutingType(), $day to restrict the
 * schedule to a single weekday for checkVolunteerRoutingTime().
 */
function routingShifts(?string $type = null, ?int $onlyDay = null): string
{
    $shifts = [];
    foreach ($onlyDay !== null ? [$onlyDay] : range(1, 7) as $day) {
        $shift = [
            "day" => $day,
            "tz" => ROUTING_SHIFT_TZ,
            "start_time" => "12:00 AM",
            "end_time" => "11:59 PM",
        ];

        if ($type !== null) {
            $shift["type"] = $type;
        }

        $shifts[] = $shift;
    }

    return base64_encode(json_encode($shifts));
}

/**
 * Built as an array rather than a VolunteerData so that the language filter can be
 * exercised: VolunteerService::getVolunteerInfo() reads `volunteer_language`, which
 * VolunteerData does not declare.
 */
function routingVolunteer(
    string $name,
    string $phoneNumber,
    int $gender = VolunteerGender::UNSPECIFIED,
    int $responder = VolunteerResponderOption::UNSPECIFIED,
    ?string $shiftSchedule = null,
    ?array $languages = null,
): array {
    $volunteer = [
        "volunteer_name" => $name,
        "volunteer_phone_number" => $phoneNumber,
        "volunteer_gender" => $gender,
        "volunteer_responder" => $responder,
        "volunteer_notes" => "",
        "volunteer_enabled" => true,
        "volunteer_shift_schedule" => $shiftSchedule ?? routingShifts(),
    ];

    if ($languages !== null) {
        $volunteer["volunteer_language"] = $languages;
    }

    return $volunteer;
}

function routingRoutingParameters(string $serviceBodyId): VolunteerRoutingParameters
{
    $params = new VolunteerRoutingParameters();
    $params->service_body_id = $serviceBodyId;
    $params->tracker = 0;
    $params->cycle_algorithm = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $params->volunteer_type = VolunteerType::PHONE;
    $params->volunteer_gender = VolunteerGender::UNSPECIFIED;
    $params->volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $params->volunteer_language = "en-US";

    return $params;
}

beforeEach(function () {
    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();
    $this->serviceBodyId = "4400";
    $this->conferenceName = "abc";

    $this->settingsService = new SettingsService();
    app()->instance(SettingsService::class, $this->settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($this->settingsService);

    $this->seedCallHandling = function (bool $genderRoutingEnabled = true) {
        $callHandling = new ServiceBodyCallHandling();
        $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
        $callHandling->service_body_id = $this->serviceBodyId;
        $callHandling->volunteer_routing_enabled = true;
        $callHandling->gender_routing_enabled = $genderRoutingEnabled;
        $callHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

        ConfigData::createServiceBodyCallHandling($this->serviceBodyId, $callHandling);
    };

    $this->seedVolunteers = function (array $volunteers) {
        ConfigData::createVolunteers($this->serviceBodyId, $volunteers);
    };

    // The volunteer pool shared by the gender cases: one volunteer of each gender, with
    // the UNSPECIFIED one first so that "route to anyone" is distinguishable from
    // "route to the man", which is the exact confusion issue #1578 is about.
    $this->seedGenderPool = function () {
        ($this->seedVolunteers)([
            routingVolunteer("Unspecified", ROUTING_UNSPECIFIED_NUMBER, VolunteerGender::UNSPECIFIED),
            routingVolunteer("Male", ROUTING_MALE_NUMBER, VolunteerGender::MALE),
            routingVolunteer("Female", ROUTING_FEMALE_NUMBER, VolunteerGender::FEMALE),
        ]);
    };

    $this->dialerDebugConfig = function () {
        $response = $this->call('GET', '/helpline-dialer.php', [
            'Debug' => "1",
            'SearchType' => "1",
            'Called' => "+12125551212",
            'FriendlyName' => $this->conferenceName,
        ]);

        $response->assertStatus(200)->assertHeader("Content-Type", "application/json");

        return $response->json();
    };

    $this->getVolunteerFor = function (VolunteerRoutingParameters $params) {
        return app(VolunteerService::class)->getHelplineVolunteer($params);
    };
});

/*
|--------------------------------------------------------------------------
| Gender routing through the live call path
|--------------------------------------------------------------------------
|
| These go through HelplineController::getCallConfig(), which is where the
| session("Gender") value is read into the routing parameters. The FEMALE and
| NO_PREFERENCE cases fail against the pre-fix `session()->has("Gender")`.
*/

test('the gender the caller selected is carried into the routing parameters', function ($selectedGender, $expectedGender) {
    session()->put('override_service_body_id', $this->serviceBodyId);
    ($this->seedCallHandling)();
    ($this->seedGenderPool)();

    if ($selectedGender !== null) {
        session()->put('Gender', $selectedGender);
    }

    $config = ($this->dialerDebugConfig)();

    expect($config['volunteer_routing_params']['volunteer_gender'])->toBe($expectedGender);
})->with([
    'caller made no gender selection' => [null, VolunteerGender::UNSPECIFIED],
    'caller pressed 1 for a man' => [VolunteerGender::MALE, VolunteerGender::MALE],
    'caller pressed 2 for a woman' => [VolunteerGender::FEMALE, VolunteerGender::FEMALE],
    'caller pressed 3 for either' => [VolunteerGender::NO_PREFERENCE, VolunteerGender::NO_PREFERENCE],
]);

test('the volunteer dialed matches the gender the caller selected', function ($selectedGender, $expectedNumber) {
    session()->put('override_service_body_id', $this->serviceBodyId);
    ($this->seedCallHandling)();
    ($this->seedGenderPool)();

    if ($selectedGender !== null) {
        session()->put('Gender', $selectedGender);
    }

    $config = ($this->dialerDebugConfig)();

    expect($config['volunteer']['phoneNumber'])->toBe($expectedNumber);
})->with([
    // With no selection or no preference the filter short-circuits and the first
    // volunteer in the pool answers, whatever their gender.
    'caller made no gender selection' => [null, ROUTING_UNSPECIFIED_NUMBER],
    'caller pressed 3 for either' => [VolunteerGender::NO_PREFERENCE, ROUTING_UNSPECIFIED_NUMBER],
    'caller pressed 1 for a man' => [VolunteerGender::MALE, ROUTING_MALE_NUMBER],
    'caller pressed 2 for a woman' => [VolunteerGender::FEMALE, ROUTING_FEMALE_NUMBER],
]);

test('a caller who asks for a woman is never handed to a man', function () {
    session()->put('override_service_body_id', $this->serviceBodyId);
    ($this->seedCallHandling)();
    ($this->seedVolunteers)([
        routingVolunteer("Male", ROUTING_MALE_NUMBER, VolunteerGender::MALE),
    ]);

    session()->put('Gender', VolunteerGender::FEMALE);

    $config = ($this->dialerDebugConfig)();

    expect($config['volunteer']['phoneNumber'])
        ->not->toBe(ROUTING_MALE_NUMBER)
        ->toBe(SpecialPhoneNumber::UNKNOWN);
});

test('gender routing is inert when the service body has it turned off', function () {
    session()->put('override_service_body_id', $this->serviceBodyId);
    ($this->seedCallHandling)(genderRoutingEnabled: false);
    ($this->seedGenderPool)();

    $config = ($this->dialerDebugConfig)();

    expect($config['volunteer_routing_params']['volunteer_gender'])->toBe(VolunteerGender::UNSPECIFIED)
        ->and($config['volunteer']['phoneNumber'])->toBe(ROUTING_UNSPECIFIED_NUMBER);
});

/*
|--------------------------------------------------------------------------
| The rest of the VolunteerRoutingHelpers filter matrix
|--------------------------------------------------------------------------
|
| getCallConfig() hard-codes volunteer_type, volunteer_responder and
| volunteer_language, so the remaining filters are driven straight through
| VolunteerService with explicit routing parameters.
*/

test('gender filters the volunteer pool', function ($gender, $expectedNumber) {
    ($this->seedGenderPool)();

    $params = routingRoutingParameters($this->serviceBodyId);
    $params->volunteer_gender = $gender;

    expect(($this->getVolunteerFor)($params)->phoneNumber)->toBe($expectedNumber);
})->with([
    'unspecified takes anyone' => [VolunteerGender::UNSPECIFIED, ROUTING_UNSPECIFIED_NUMBER],
    'no preference takes anyone' => [VolunteerGender::NO_PREFERENCE, ROUTING_UNSPECIFIED_NUMBER],
    'male' => [VolunteerGender::MALE, ROUTING_MALE_NUMBER],
    'female' => [VolunteerGender::FEMALE, ROUTING_FEMALE_NUMBER],
]);

test('volunteer type filters the volunteer pool', function ($type, $expectedNumber) {
    ($this->seedVolunteers)([
        routingVolunteer("Sms Only", ROUTING_FEMALE_NUMBER, shiftSchedule: routingShifts(VolunteerType::SMS)),
        routingVolunteer("Phone Only", ROUTING_MALE_NUMBER, shiftSchedule: routingShifts(VolunteerType::PHONE)),
    ]);

    $params = routingRoutingParameters($this->serviceBodyId);
    $params->volunteer_type = $type;

    expect(($this->getVolunteerFor)($params)->phoneNumber)->toBe($expectedNumber);
})->with([
    'phone calls skip the sms-only volunteer' => [VolunteerType::PHONE, ROUTING_MALE_NUMBER],
    'sms skips the phone-only volunteer' => [VolunteerType::SMS, ROUTING_FEMALE_NUMBER],
]);

test('language filters the volunteer pool', function ($language, $expectedNumber) {
    $this->settingsService->set('language_selections', 'en-US,es-ES');
    ($this->seedVolunteers)([
        routingVolunteer("Spanish", ROUTING_FEMALE_NUMBER, languages: ["es-ES"]),
        routingVolunteer("English", ROUTING_MALE_NUMBER, languages: ["en-US"]),
    ]);

    $params = routingRoutingParameters($this->serviceBodyId);
    $params->volunteer_language = $language;

    expect(($this->getVolunteerFor)($params)->phoneNumber)->toBe($expectedNumber);
})->with([
    'english' => ["en-US", ROUTING_MALE_NUMBER],
    'spanish' => ["es-ES", ROUTING_FEMALE_NUMBER],
]);

test('the responder filter restricts the pool to responders', function () {
    ($this->seedVolunteers)([
        routingVolunteer("Not A Responder", ROUTING_FEMALE_NUMBER, responder: VolunteerResponderOption::UNSPECIFIED),
        routingVolunteer("Responder", ROUTING_MALE_NUMBER, responder: VolunteerResponderOption::ENABLED),
    ]);

    $params = routingRoutingParameters($this->serviceBodyId);
    $params->volunteer_responder = VolunteerResponderOption::ENABLED;

    expect(($this->getVolunteerFor)($params)->phoneNumber)->toBe(ROUTING_MALE_NUMBER);
});

test('an unspecified responder requirement takes anyone', function () {
    ($this->seedVolunteers)([
        routingVolunteer("Not A Responder", ROUTING_FEMALE_NUMBER, responder: VolunteerResponderOption::UNSPECIFIED),
        routingVolunteer("Responder", ROUTING_MALE_NUMBER, responder: VolunteerResponderOption::ENABLED),
    ]);

    $params = routingRoutingParameters($this->serviceBodyId);

    expect(($this->getVolunteerFor)($params)->phoneNumber)->toBe(ROUTING_FEMALE_NUMBER);
});

test('a volunteer who is not on shift right now is skipped', function () {
    date_default_timezone_set(ROUTING_SHIFT_TZ);
    $today = (int)(new DateTime())->format('N') % 7 + 1;
    $tomorrow = $today % 7 + 1;

    ($this->seedVolunteers)([
        routingVolunteer("Off Shift", ROUTING_FEMALE_NUMBER, shiftSchedule: routingShifts(onlyDay: $tomorrow)),
        routingVolunteer("On Shift", ROUTING_MALE_NUMBER, shiftSchedule: routingShifts(onlyDay: $today)),
    ]);

    $params = routingRoutingParameters($this->serviceBodyId);

    expect(($this->getVolunteerFor)($params)->phoneNumber)->toBe(ROUTING_MALE_NUMBER);
});

test('nobody on shift falls through to the unknown number', function () {
    date_default_timezone_set(ROUTING_SHIFT_TZ);
    $tomorrow = ((int)(new DateTime())->format('N') % 7 + 1) % 7 + 1;

    ($this->seedVolunteers)([
        routingVolunteer("Off Shift", ROUTING_FEMALE_NUMBER, shiftSchedule: routingShifts(onlyDay: $tomorrow)),
    ]);

    $params = routingRoutingParameters($this->serviceBodyId);

    expect(($this->getVolunteerFor)($params)->phoneNumber)->toBe(SpecialPhoneNumber::UNKNOWN);
});
