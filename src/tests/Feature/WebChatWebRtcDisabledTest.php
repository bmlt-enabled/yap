<?php

/**
 * WebChat and WebRTC ship in 5.0.0 as EXPERIMENTAL and disabled by default.
 *
 * "Disabled" has to mean unreachable, not reachable-but-refusing, so these
 * tests pin the shipping posture: with webchat_enabled and webrtc_enabled
 * false (the defaults, and what config.test.php leaves unset), none of the
 * routes are registered and the SMS webhook does no chat-session routing.
 *
 * These are gate tests only. They deliberately do not exercise WebChat/WebRTC
 * internals, which remain untested until the features are promoted in 5.1.0.
 */

use App\Http\Controllers\WebChatSmsController;
use App\Services\ChatSessionService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/** Every route that must disappear when the feature flags are off. */
function experimentalWidgetRouteUris(): array
{
    return [
        // routes/web.php - Twilio-facing webhooks
        'webrtc-call',
        'webrtc-status',
        'webchat-sms',
        // routes/api.php - public widget endpoints
        'api/v1/webrtc/token',
        'api/v1/webrtc/config',
        'api/v1/webchat/config',
        'api/v1/webchat/meetings',
        'api/v1/webchat/session',
        'api/v1/webchat/session/{sessionId}/message',
        'api/v1/webchat/session/{sessionId}/messages',
        'api/v1/webchat/session/{sessionId}/close',
    ];
}

test('webchat and webrtc are disabled by default', function () {
    $settings = new SettingsService();

    expect($settings->get('webchat_enabled'))->toBeFalsy();
    expect($settings->get('webrtc_enabled'))->toBeFalsy();
});

test('no webchat or webrtc route is registered while the features are disabled', function () {
    $registered = collect(Route::getRoutes()->getRoutes())
        ->map(fn($route) => $route->uri())
        ->all();

    foreach (experimentalWidgetRouteUris() as $uri) {
        expect($registered)->not->toContain($uri);
    }
});

test('the route table has no webchat or webrtc routes at all while disabled', function () {
    $matching = collect(Route::getRoutes()->getRoutes())
        ->map(fn($route) => $route->uri())
        ->filter(fn($uri) => str_contains($uri, 'webchat') || str_contains($uri, 'webrtc'))
        ->values()
        ->all();

    expect($matching)->toBeEmpty();
});

test('disabled webchat and webrtc endpoints return 404', function ($uri) {
    expect($this->call('GET', $uri)->status())->toBe(404);
    expect($this->call('POST', $uri)->status())->toBe(404);
})->with([
    '/webrtc-call',
    '/webrtc-status',
    '/webchat-sms',
    '/api/v1/webrtc/token',
    '/api/v1/webrtc/config',
    '/api/v1/webchat/config',
    '/api/v1/webchat/meetings',
    '/api/v1/webchat/session',
]);

test('handleSms returns empty TwiML and never touches ChatSessionService when webchat is disabled', function () {
    // Fail loudly if the controller resolves the chat service at all: routing an
    // inbound SMS to a session by phone number is exactly what must not happen.
    app()->bind(ChatSessionService::class, function () {
        throw new RuntimeException('ChatSessionService must not be resolved while webchat is disabled');
    });

    $controller = new WebChatSmsController(new SettingsService());

    $response = $controller->handleSms(Request::create('/webchat-sms', 'POST', [
        'From' => '+15551234567',
        'To' => '+15559876543',
        'Body' => 'a volunteer reply that must not be routed',
    ]));

    expect($response->headers->get('Content-Type'))->toContain('text/xml');
    expect($response->getContent())->toContain('<Response');
    // Empty TwiML: no <Message> verb, nothing delivered anywhere.
    expect($response->getContent())->not->toContain('<Message');
});

test('featureEnabledAtBoot reports the flags as off and fails closed on an unknown setting', function () {
    expect(SettingsService::featureEnabledAtBoot('webchat_enabled'))->toBeFalse();
    expect(SettingsService::featureEnabledAtBoot('webrtc_enabled'))->toBeFalse();
    expect(SettingsService::featureEnabledAtBoot('a_setting_that_does_not_exist'))->toBeFalse();
});

test('webchat and webrtc settings do not advertise documentation pages that do not exist', function () {
    $allowlist = (new SettingsService())->allowlist();

    $widgetSettings = collect($allowlist)
        ->filter(fn($_, $name) => str_starts_with($name, 'webchat_')
            || str_starts_with($name, 'webrtc_')
            || str_starts_with($name, 'twilio_api_')
            || $name === 'twilio_twiml_app_sid');

    expect($widgetSettings)->not->toBeEmpty();

    foreach ($widgetSettings as $name => $definition) {
        expect($definition['description'] ?? '')
            ->toBe('', "Setting '{$name}' links to documentation that has not been written yet");
    }
});
