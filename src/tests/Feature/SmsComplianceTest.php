<?php

use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeEach(function () {
    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();
});

test('formatSmsComplianceMessage with title and default opt-out message', function () {
    $settingsService = new SettingsService();
    $settingsService->set("title", "Metro Detroit Region of NA");
    app()->instance(SettingsService::class, $settingsService);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    $message = "Meeting Results, click here: https://example.com/msr/42.49/-83.23";
    $result = $this->twilioService->formatSmsComplianceMessage($message);

    expect($result)->toBe(
        "Metro Detroit Region of NA\n" .
        "Meeting Results, click here: https://example.com/msr/42.49/-83.23 – {Reply STOP to opt-out}"
    );
});

test('formatSmsComplianceMessage with title and custom opt-out message', function () {
    $settingsService = new SettingsService();
    $settingsService->set("title", "My Helpline");
    $settingsService->set("sms_opt_out_message", "Text STOP to unsubscribe");
    app()->instance(SettingsService::class, $settingsService);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    $message = "Test meeting info";
    $result = $this->twilioService->formatSmsComplianceMessage($message);

    expect($result)->toBe(
        "My Helpline\n" .
        "Test meeting info – {Text STOP to unsubscribe}"
    );
});

test('formatSmsComplianceMessage without title', function () {
    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    $message = "Test meeting info";
    $result = $this->twilioService->formatSmsComplianceMessage($message);

    expect($result)->toBe("Test meeting info – {Reply STOP to opt-out}");
});

test('formatSmsComplianceMessage with empty opt-out message disables opt-out text', function () {
    $settingsService = new SettingsService();
    $settingsService->set("title", "My Helpline");
    $settingsService->set("sms_opt_out_message", "");
    app()->instance(SettingsService::class, $settingsService);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    $message = "Test meeting info";
    $result = $this->twilioService->formatSmsComplianceMessage($message);

    expect($result)->toBe("My Helpline\nTest meeting info");
});
