<?php

use App\Services\SettingsService;

/*
 * Every setting in SettingsService::$allowlist must be referenced by a test.
 *
 * "Referenced" is a grep, the same one #1682 used to find the 30 untested
 * settings: the setting's name (or override_<name>) as a whole word anywhere
 * under tests/, e2e/ or app/Simulator/. It is a floor, not proof of coverage,
 * but it catches a new setting that ships with no test at all.
 *
 * The settings below were untested when this check was added. The list may only
 * shrink: give one a test and the check will tell you to delete it from here.
 */
const SETTINGS_KNOWN_UNTESTED = [
    // Settings that are not overridable per call, read at boot or by the admin UI.
    'bmlt_auth' => 'decides whether admin logins are checked against BMLT',
    'service_body_config_id' => 'an alternative to service_body_id for picking a call\'s configuration',
    // The experimental web widgets, off by default.
    'twilio_api_key' => 'WebRTC widget credential',
    'twilio_api_secret' => 'WebRTC widget credential',
    'webrtc_allowed_origins' => 'WebRTC widget',
    'webrtc_token_rate_limit' => 'WebRTC widget',
    'webrtc_call_rate_limit' => 'WebRTC widget',
    'webchat_timeout_minutes' => 'web chat widget',
    'webchat_meeting_search_enabled' => 'web chat widget',
    'webchat_no_coverage_message' => 'web chat widget',
    'webchat_no_volunteers_message' => 'web chat widget',
    'webchat_volunteer_sms_prefix' => 'web chat widget',
    'webchat_rate_limit' => 'web chat widget',
    // Nothing reads it: voicemail playback was removed in #683.
    'voicemail_playback_grace_hours' => 'unused',
];

/**
 * The allowlist as written, without the per-language prompt and voice entries
 * the constructor generates from the available languages.
 *
 * @return list<string>
 */
function declaredSettings(): array
{
    $allowlist = (new ReflectionProperty(SettingsService::class, 'allowlist'))->getDefaultValue();

    return array_keys($allowlist);
}

/**
 * All the text the check searches, minus this file (which names every setting
 * in its baseline).
 */
function testSources(): string
{
    $root = dirname(__DIR__, 2);
    $sources = '';
    foreach (['tests', 'e2e', 'app/Simulator'] as $dir) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'js'], true)
                && $file->getRealPath() !== realpath(__FILE__)) {
                $sources .= file_get_contents($file->getPathname()) . "\n";
            }
        }
    }

    return $sources;
}

function isReferenced(string $setting, string $sources): bool
{
    $name = preg_quote($setting, '/');

    // jft_option, override_jft_option, {SETTING_MEETING_SEARCH_RADIUS} ... but
    // not "voice" inside "en_US_voice".
    return preg_match("/(?<![A-Za-z0-9_])(?:override_)?{$name}(?![A-Za-z0-9_])/", $sources) === 1
        || preg_match('/\{SETTING_' . strtoupper($name) . '\}/', $sources) === 1;
}

test('every setting in the allowlist is referenced by a test', function () {
    $sources = testSources();

    $untested = array_values(array_filter(
        declaredSettings(),
        fn (string $setting) => !array_key_exists($setting, SETTINGS_KNOWN_UNTESTED) && !isReferenced($setting, $sources)
    ));

    expect($untested)->toBe([], sprintf(
        "These settings are in SettingsService::\$allowlist but no test mentions them: %s.\n"
        . "Add a test that asserts what each one changes (see tests/Feature/RoutingSettingsMatrixTest.php).",
        implode(', ', $untested)
    ));
});

test('the known-untested list only holds settings that still exist and are still untested', function () {
    $sources = testSources();
    $declared = declaredSettings();

    $gone = array_values(array_diff(array_keys(SETTINGS_KNOWN_UNTESTED), $declared));
    expect($gone)->toBe([], 'No longer in the allowlist; delete from SETTINGS_KNOWN_UNTESTED: ' . implode(', ', $gone));

    $nowTested = array_values(array_filter(
        array_keys(SETTINGS_KNOWN_UNTESTED),
        fn (string $setting) => isReferenced($setting, $sources)
    ));
    expect($nowTested)->toBe([], 'These have tests now; delete them from SETTINGS_KNOWN_UNTESTED: ' . implode(', ', $nowTested));
});

test('the reference check matches the ways tests name a setting, and only those', function () {
    expect(isReferenced('jft_option', "set('jft_option', true)"))->toBeTrue()
        ->and(isReferenced('jft_option', "session()->put('override_jft_option', 1)"))->toBeTrue()
        ->and(isReferenced('jft_option', '?override_jft_option=true'))->toBeTrue()
        ->and(isReferenced('meeting_search_radius', '&geo_width={SETTING_MEETING_SEARCH_RADIUS}'))->toBeTrue()
        ->and(isReferenced('voice', "'en_US_voice' => 'alice'"))->toBeFalse()
        ->and(isReferenced('jft_option', "'jft_options'"))->toBeFalse();
});
