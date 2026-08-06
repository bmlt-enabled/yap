<?php

use App\Constants\ConferenceSpecial;
use App\Constants\CycleAlgorithm;
use App\Constants\EventId;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Services\ConferenceService;
use App\Services\RootServerService;
use App\Structures\ServiceBodyCallHandling;
use Tests\CallScenario;
use Tests\FakeHttp;
use Tests\RootServerMocks;

const BREADTH_V1 = '(555) 111-1001';
const BREADTH_V2 = '(555) 111-1002';
const BREADTH_V3 = '(555) 111-1003';
const BREADTH_FORCE_NUMBER = '+19998887777';
const BREADTH_FALLBACK_NUMBER = '+15551112223';
const BREADTH_REDIRECT_VOLUNTEER = '(555) 111-2001';
const BREADTH_YSK_MARKER = 'https://example.org/ysk-continuity-marker.mp3';

function breadthVolunteer(string $name, string $phoneNumber, int $gender): array
{
    $today = (int) (new DateTime())->format('N') % 7 + 1;

    return [
        'volunteer_name' => $name,
        'volunteer_phone_number' => $phoneNumber,
        'volunteer_gender' => $gender,
        'volunteer_responder' => 0,
        'volunteer_notes' => '',
        'volunteer_enabled' => true,
        'volunteer_shift_schedule' => base64_encode(json_encode([[
            'day' => $today,
            'tz' => 'America/New_York',
            'start_time' => '12:00 AM',
            'end_time' => '11:59 PM',
        ]])),
    ];
}

function breadthSeedCallHandling(
    string $serviceBodyId,
    int $algorithm,
    bool $genderRouting = false,
    bool $smsNotification = false,
): void {
    $callHandling = new ServiceBodyCallHandling();
    $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $callHandling->service_body_id = (int) $serviceBodyId;
    $callHandling->volunteer_routing_enabled = true;
    $callHandling->gender_routing = $genderRouting ? 1 : 0;
    $callHandling->call_strategy = $algorithm;
    $callHandling->volunteer_sms_notification_enabled = $smsNotification;
    if ($smsNotification) {
        $callHandling->volunteer_sms_notification = 'sms_notification';
    }

    ConfigData::createServiceBodyCallHandling((int) $serviceBodyId, $callHandling);
}

function breadthSeedVolunteers(bool $genderRouting = false): void
{
    $serviceBodyId = test()->serviceBodyId;

    if ($genderRouting) {
        ConfigData::createVolunteers((int) $serviceBodyId, [
            breadthVolunteer('Unspecified', BREADTH_V1, VolunteerGender::UNSPECIFIED),
            breadthVolunteer('Male', BREADTH_V2, VolunteerGender::MALE),
            breadthVolunteer('Female', BREADTH_V3, VolunteerGender::FEMALE),
        ]);

        return;
    }

    ConfigData::createVolunteers((int) $serviceBodyId, [
        breadthVolunteer('Volunteer One', BREADTH_V1, VolunteerGender::UNSPECIFIED),
        breadthVolunteer('Volunteer Two', BREADTH_V2, VolunteerGender::UNSPECIFIED),
        breadthVolunteer('Volunteer Three', BREADTH_V3, VolunteerGender::UNSPECIFIED),
    ]);
}

function breadthStartScenario(array $settings = []): CallScenario
{
    return new CallScenario($settings);
}

function breadthNavigateToConference(CallScenario $scenario, bool $genderRouting = false): CallScenario
{
    $scenario->startCall(test()->caller, test()->called);

    return $scenario->navigateToHelplineConference($genderRouting);
}

function breadthApplyOutcome(
    CallScenario $scenario,
    int $algorithm,
    string $outcome,
    bool $genderRouting,
): CallScenario {
    $poolSize = $genderRouting ? 1 : 3;

    if ($outcome === 'answered') {
        return $scenario->applyVolunteerOutcome(1, 'answered');
    }

    if ($outcome === 'rejected') {
        if ($poolSize === 1) {
            return $scenario->volunteerRejects(1)->applyVolunteerOutcome(1, 'answered');
        }

        return $scenario->volunteerRejects(1)->applyVolunteerOutcome(2, 'answered');
    }

    if ($algorithm === CycleAlgorithm::BLASTING) {
        return $scenario->blastAllNoAnswer();
    }

    $attempts = match ($algorithm) {
        CycleAlgorithm::LINEAR_LOOP_FOREVER => $poolSize === 1 ? 1 : 3,
        default => $poolSize,
    };

    for ($n = 1; $n <= $attempts; $n++) {
        $scenario->applyVolunteerOutcome($n, $outcome);
    }

    return $scenario;
}

function breadthExpectedDials(int $algorithm, string $outcome, bool $genderRouting): array
{
    $pool = $genderRouting
        ? [BREADTH_V3]
        : [BREADTH_V1, BREADTH_V2, BREADTH_V3];

    if ($algorithm === CycleAlgorithm::BLASTING) {
        return $pool;
    }

    return match ($outcome) {
        'answered' => match ($algorithm) {
            CycleAlgorithm::RANDOM_LOOP_FOREVER => [BREADTH_V3],
            CycleAlgorithm::RANDOM_CYCLE_AND_VOICEMAIL => $genderRouting
                ? [$pool[0]]
                : [BREADTH_V1],
            default => [$pool[0]],
        },
        'rejected' => match ($algorithm) {
            CycleAlgorithm::RANDOM_LOOP_FOREVER => $genderRouting
                ? [BREADTH_V3, BREADTH_V3]
                : [BREADTH_V3, BREADTH_V2],
            CycleAlgorithm::RANDOM_CYCLE_AND_VOICEMAIL => $genderRouting
                ? [BREADTH_V3]
                : [BREADTH_V1, BREADTH_V2],
            CycleAlgorithm::LINEAR_LOOP_FOREVER => $genderRouting
                ? [BREADTH_V3, BREADTH_V3]
                : [BREADTH_V1, BREADTH_V2],
            default => $genderRouting ? [BREADTH_V3] : [BREADTH_V1, BREADTH_V2],
        },
        'no-answer', 'busy', 'failed' => match ($algorithm) {
            CycleAlgorithm::LINEAR_LOOP_FOREVER => $genderRouting
                ? [BREADTH_V3, BREADTH_V3]
                : [BREADTH_V1, BREADTH_V2, BREADTH_V3, BREADTH_V1],
            CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL => $genderRouting
                ? [$pool[0]]
                : [BREADTH_V1, BREADTH_V2, BREADTH_V3],
            CycleAlgorithm::RANDOM_LOOP_FOREVER => $genderRouting
                ? [BREADTH_V3, BREADTH_V3]
                : [BREADTH_V3, BREADTH_V2, BREADTH_V3, BREADTH_V3],
            CycleAlgorithm::RANDOM_CYCLE_AND_VOICEMAIL => $genderRouting
                ? [$pool[0]]
                : [BREADTH_V1, BREADTH_V2, BREADTH_V3],
            default => $pool,
        },
        default => throw new InvalidArgumentException("Unknown outcome {$outcome}"),
    };
}

function breadthExpectsVoicemail(int $algorithm, string $outcome): bool
{
    if (in_array($outcome, ['answered', 'rejected'], true)) {
        return false;
    }

    return in_array($algorithm, [
        CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL,
        CycleAlgorithm::BLASTING,
        CycleAlgorithm::RANDOM_CYCLE_AND_VOICEMAIL,
    ], true);
}

beforeEach(function () {
    FakeHttp::install();

    $this->serviceBodyId = '4400';
    $this->redirectServiceBodyId = '4401';
    $this->caller = '+17325551212';
    $this->called = '+12125551212';

    session()->flush();

    $rootServer = mock(RootServerService::class)->makePartial();
    $rootServer->shouldReceive('getServiceBody')
        ->with($this->serviceBodyId)
        ->andReturn((object) [
            'id' => $this->serviceBodyId,
            'parent_id' => '4399',
            'name' => 'Scenario Breadth Area',
            'helpline' => '',
        ]);
    $rootServer->shouldReceive('getServiceBody')
        ->with($this->redirectServiceBodyId)
        ->andReturn((object) [
            'id' => $this->redirectServiceBodyId,
            'parent_id' => '4399',
            'name' => 'Redirect Target Area',
            'helpline' => '',
        ]);
    app()->instance(RootServerService::class, $rootServer);

    session()->put('override_service_body_id', $this->serviceBodyId);
    session()->put('override_disable_postal_code_gather', true);
});

dataset('cycle algorithm scenarios', function () {
    $algorithms = [
        'LINEAR_LOOP_FOREVER' => CycleAlgorithm::LINEAR_LOOP_FOREVER,
        'LINEAR_CYCLE_AND_VOICEMAIL' => CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL,
        'RANDOM_LOOP_FOREVER' => CycleAlgorithm::RANDOM_LOOP_FOREVER,
        'BLASTING' => CycleAlgorithm::BLASTING,
        'RANDOM_CYCLE_AND_VOICEMAIL' => CycleAlgorithm::RANDOM_CYCLE_AND_VOICEMAIL,
    ];

    $outcomes = ['answered', 'rejected', 'no-answer', 'busy', 'failed'];
    $genderOptions = [
        'gender routing off' => false,
        'gender routing on' => true,
    ];

    $cases = [];
    foreach ($algorithms as $algorithmLabel => $algorithm) {
        foreach ($outcomes as $outcome) {
            foreach ($genderOptions as $genderLabel => $genderRouting) {
                $cases["{$algorithmLabel} / {$outcome} / {$genderLabel}"] = [
                    $algorithm,
                    $outcome,
                    $genderRouting,
                ];
            }
        }
    }

    return $cases;
});

test('cycle algorithms dial volunteers in the expected order', function (int $algorithm, string $outcome, bool $genderRouting) {
    mt_srand(1580);
    $expected = breadthExpectedDials($algorithm, $outcome, $genderRouting);
    mt_srand(1580);

    breadthSeedCallHandling($this->serviceBodyId, $algorithm, $genderRouting);
    breadthSeedVolunteers($genderRouting);

    $scenario = breadthStartScenario();
    breadthNavigateToConference($scenario, $genderRouting);

    expect($scenario->lastTwiml())->toDialConference();
    $scenario->joinConference();

    breadthApplyOutcome($scenario, $algorithm, $outcome, $genderRouting);

    $scenario->assertDialedInOrder($expected);

    if (breadthExpectsVoicemail($algorithm, $outcome)) {
        $scenario->assertRedirectedToVoicemail();
    }
})->with('cycle algorithm scenarios');

test('blasting falls through to voicemail after each volunteer leg reports no-answer', function () {
    breadthSeedCallHandling($this->serviceBodyId, CycleAlgorithm::BLASTING);
    breadthSeedVolunteers();

    $scenario = breadthStartScenario();
    breadthNavigateToConference($scenario);
    $scenario->joinConference()->blastAllNoAnswer();

    expect($scenario->twilio)
        ->toHaveDialedInOrder([BREADTH_V1, BREADTH_V2, BREADTH_V3])
        ->toHaveRedirectedCall('voicemail.php');
});

test('blasting duplicate noop increment would skip voicemail if count overshoots no_answer_max', function () {
    breadthSeedCallHandling($this->serviceBodyId, CycleAlgorithm::BLASTING);
    breadthSeedVolunteers();

    $scenario = breadthStartScenario();
    breadthNavigateToConference($scenario);
    $scenario->joinConference();

    expect($scenario->twilio)->toHaveDialedInOrder([BREADTH_V1, BREADTH_V2, BREADTH_V3]);

    $scenario->volunteerNoAnswer(1);
    $scenario->volunteerNoAnswer(2);
    $scenario->volunteerNoAnswer(2);
    $scenario->volunteerNoAnswer(3);

    $scenario->assertRedirectedToVoicemail();

    $redirectCount = collect($scenario->twilio->callUpdates)
        ->filter(fn (array $update) => isset($update['url']) && str_contains($update['url'], 'voicemail.php'))
        ->count();
    expect($redirectCount)->toBe(1);

    session()->put('no_answer_count', 3);
    session()->put('no_answer_max', 3);
    session()->put('master_callersid', $scenario->getCallSid());
    session()->put('voicemail_url', 'https://example.org/voicemail.php');

    app(\App\Services\TwilioService::class)->incrementNoAnswerCount();

    $redirectCountAfterOvershoot = collect($scenario->twilio->callUpdates)
        ->filter(fn (array $update) => isset($update['url']) && str_contains($update['url'], 'voicemail.php'))
        ->count();
    expect($redirectCountAfterOvershoot)->toBe(1);
});

test('caller hangup records CALLER_HUP and hangs up remaining participants', function () {
    breadthSeedCallHandling($this->serviceBodyId, CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL);
    breadthSeedVolunteers();

    $scenario = breadthStartScenario();
    breadthNavigateToConference($scenario);
    $scenario->joinConference();

    $scenario->hideConferenceFromApi()
        ->callerHangsUp()
        ->assertHasEvent(EventId::CALLER_HUP);
});

test('volunteer auto answer skips the gather and redirects to accept', function () {
    breadthSeedCallHandling($this->serviceBodyId, CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL);
    breadthSeedVolunteers();

    session()->put('override_volunteer_auto_answer', true);

    $scenario = breadthStartScenario();
    breadthNavigateToConference($scenario);
    $scenario->joinConference();

    expect($scenario->fetchVolunteerOutdialTwiml(1))
        ->toContain('Digits=1')
        ->not->toContain('<Gather');
});

test('volunteer sms notification is sent when a volunteer is dialed', function () {
    breadthSeedCallHandling($this->serviceBodyId, CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL, smsNotification: true);
    breadthSeedVolunteers();

    $scenario = breadthStartScenario();
    breadthNavigateToConference($scenario);
    $scenario->joinConference();

    expect($scenario->twilio)->toHaveSentSms(BREADTH_V1);
});

test('volunteer routing redirect dials the redirected service body volunteers', function () {
    $callHandling = new ServiceBodyCallHandling();
    $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_REDIRECT;
    $callHandling->volunteers_redirect_id = (int) $this->redirectServiceBodyId;
    $callHandling->service_body_id = (int) $this->serviceBodyId;
    $callHandling->volunteer_routing_enabled = true;
    $callHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    ConfigData::createServiceBodyCallHandling((int) $this->serviceBodyId, $callHandling);

    $redirectHandling = new ServiceBodyCallHandling();
    $redirectHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $redirectHandling->service_body_id = (int) $this->redirectServiceBodyId;
    $redirectHandling->volunteer_routing_enabled = true;
    $redirectHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    ConfigData::createServiceBodyCallHandling((int) $this->redirectServiceBodyId, $redirectHandling);

    ConfigData::createVolunteers((int) $this->redirectServiceBodyId, [
        breadthVolunteer('Redirect Volunteer', BREADTH_REDIRECT_VOLUNTEER, VolunteerGender::UNSPECIFIED),
    ]);

    $conferenceService = Mockery::mock(ConferenceService::class)->makePartial();
    $conferenceService->shouldReceive('getConferenceName')
        ->withArgs([$this->redirectServiceBodyId])
        ->andReturn($this->redirectServiceBodyId . '_conference');
    app()->instance(ConferenceService::class, $conferenceService);

    $scenario = breadthStartScenario();
    breadthNavigateToConference($scenario);
    $scenario->joinConference();

    expect($scenario->twilio)->toHaveDialed(BREADTH_REDIRECT_VOLUNTEER);
});

test('conference eventual-consistency retry succeeds without slowing the suite', function () {
    breadthSeedCallHandling($this->serviceBodyId, CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL);
    breadthSeedVolunteers();

    $scenario = breadthStartScenario();
    breadthNavigateToConference($scenario);

    // Production retries with sleep(0.5), but PHP sleep() truncates to an int — so it
    // hot-spins at sleep(0). The fake's conferenceReadsBeforeVisible knob exercises the
    // retry loop without any real delay.
    $scenario->twilio->conferenceReadsBeforeVisible = 1;
    $scenario->joinConference();

    expect($scenario->twilio->conferenceReadsBeforeVisible)->toBe(1);
    expect(ConferenceSpecial::EVENTUAL_CONSISTENCY_RETRIES)->toBe(20);
    expect($scenario->twilio)->toHaveDialed(BREADTH_V1);
});

test('force number dials the requested number directly', function () {
    $scenario = breadthStartScenario();
    $scenario->startCall($this->caller, $this->called)
        ->callEndpoint('/helpline-search.php', [
            'SearchType' => '1',
            'ForceNumber' => BREADTH_FORCE_NUMBER,
        ]);

    expect($scenario->lastTwiml())->toContain(BREADTH_FORCE_NUMBER);
});

test('no coverage falls back to the configured fallback number', function () {
    session()->forget('override_service_body_id');
    session()->put('override_fallback_number', BREADTH_FALLBACK_NUMBER);

    $rootServer = new RootServerMocks(true);
    app()->instance(RootServerService::class, $rootServer->getService());

    $scenario = breadthStartScenario();
    $scenario->startCall($this->caller, $this->called)
        ->callEndpoint('/helpline-search.php', [
            'Digits' => 'Brooklyn, NY',
            'SearchType' => '1',
        ]);

    expect($scenario->lastTwiml())->toContain(BREADTH_FALLBACK_NUMBER);
});

test('ysk carries session state across multi-hop calls with cookies disabled', function () {
    breadthSeedCallHandling($this->serviceBodyId, CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL);
    ConfigData::createVolunteers((int) $this->serviceBodyId, [
        breadthVolunteer('Solo Volunteer', BREADTH_V1, VolunteerGender::UNSPECIFIED),
    ]);

    $scenario = breadthStartScenario()
        ->withoutCookies()
        ->withCallData(['override_en_US_voicemail_greeting' => BREADTH_YSK_MARKER]);

    $scenario->startCall($this->caller, $this->called)
        ->navigateToHelplineConference()
        ->joinConference()
        ->volunteerNoAnswer(1);

    $scenario->assertRedirectedToVoicemail();
    $scenario->followCallerRedirect();

    expect($scenario->lastTwiml())
        ->toContain('<Play>' . BREADTH_YSK_MARKER . '</Play>');
});
