<?php

use App\Constants\EventId;
use App\Models\EventStatus;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('returns data', function () {
    app()->instance(EventStatus::class, Mockery::mock(EventStatus::class, function ($mock) {
        $mock->shouldReceive('all')->once();
    }));

    $response = $this->get('/api/v1/events/status');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
});

test('test event ids', function () {
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_SEARCH) == "Volunteer Search");
    $this->assertTrue(EventId::getEventById(EventId::MEETING_SEARCH) == "Meeting Search");
    $this->assertTrue(EventId::getEventById(EventId::JFT_LOOKUP) == "JFT Lookup");
    $this->assertTrue(EventId::getEventById(EventId::VOICEMAIL) == "Voicemail");
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_DIALED) == "Volunteer Dialed");
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_ANSWERED) == "Volunteer Answered");
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_REJECTED) ==  "Volunteer Rejected Call");
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_NOANSWER) ==  "Volunteer No Answer");
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_ANSWERED_BUT_CALLER_HUP) == "Volunteer Answered but Caller Hungup");
    $this->assertTrue(EventId::getEventById(EventId::CALLER_IN_CONFERENCE) == "Caller Waiting for Volunteer");
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_HUP) == "Volunteer Hungup");
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_IN_CONFERENCE) == "Volunteer Connected To Caller");
    $this->assertTrue(EventId::getEventById(EventId::CALLER_HUP) == "Caller Hungup");
    $this->assertTrue(EventId::getEventById(EventId::MEETING_SEARCH_LOCATION_GATHERED) == "Meeting Search Location Gathered");
    $this->assertTrue(EventId::getEventById(EventId::HELPLINE_ROUTE) == "Helpline Route");
    $this->assertTrue(EventId::getEventById(EventId::VOICEMAIL_PLAYBACK) == "Voicemail Playback");
    $this->assertTrue(EventId::getEventById(EventId::DIALBACK) == "Dialback");
    $this->assertTrue(EventId::getEventById(EventId::PROVINCE_LOOKUP_LIST) == "Province Lookup List");
    $this->assertTrue(EventId::getEventById(EventId::MEETING_SEARCH_SMS) == "Meeting Search via SMS");
    $this->assertTrue(EventId::getEventById(EventId::VOLUNTEER_SEARCH_SMS) == "Volunteer Search via SMS");
    $this->assertTrue(EventId::getEventById(EventId::JFT_LOOKUP_SMS) == "JFT Lookup via SMS");
    $this->assertTrue(EventId::getEventById(EventId::SMS_BLACKHOLED) == "SMS Blackholed");
    $this->assertTrue(EventId::getEventById(EventId::SPAD_LOOKUP) == "SPAD Lookup");
    $this->assertTrue(EventId::getEventById(EventId::SPAD_LOOKUP_SMS) == "SPAD Lookup via SMS");
});
