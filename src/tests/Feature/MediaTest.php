<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Http;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_SESSION = null;
    $this->utility = setupTwilioService();
    $this->recordingUrl = "https://api.twilio.com/2010-04-01/Accounts/AC222a79bf52fdc8c3cf463b2846582b83/Recordings/RE123";
});

test('media proxy streams the recording with twilio basic auth for a valid signed url', function () {
    Http::fake([
        "api.twilio.com/*" => Http::response("FAKEMP3BYTES", 200, ["Content-Type" => "audio/mpeg"]),
    ]);

    $signedUrl = MediaController::proxyUrl($this->recordingUrl);

    $response = $this->get($signedUrl);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "audio/mpeg");
    expect($response->getContent())->toBe("FAKEMP3BYTES");

    // Fetches the .mp3 rendition from Twilio, authenticated server-side.
    Http::assertSent(function ($request) {
        return $request->url() === $this->recordingUrl . ".mp3"
            && $request->hasHeader("Authorization");
    });
});

test('media proxy rejects an unsigned request', function () {
    Http::fake();

    $response = $this->get("/media?recording=" . urlencode($this->recordingUrl));

    $response->assertStatus(403);
    Http::assertNothingSent();
});

test('media proxy rejects a tampered signed url', function () {
    Http::fake();

    $signedUrl = MediaController::proxyUrl($this->recordingUrl);
    // Swap the recording out from under a signature generated for a different one.
    $tamperedUrl = str_replace("RE123", "RE999", $signedUrl);

    $response = $this->get($tamperedUrl);

    $response->assertStatus(403);
    Http::assertNothingSent();
});

test('media proxy rejects a non-twilio host even when the signature is valid', function () {
    Http::fake();

    $signedUrl = MediaController::proxyUrl("https://evil.example.com/Recordings/RE123");

    $response = $this->get($signedUrl);

    $response->assertStatus(403);
    Http::assertNothingSent();
});

test('media proxy returns a bad gateway when the twilio fetch fails', function () {
    Http::fake([
        "api.twilio.com/*" => Http::response("Unauthorized", 401),
    ]);

    $signedUrl = MediaController::proxyUrl($this->recordingUrl);

    $response = $this->get($signedUrl);

    $response->assertStatus(502);
});

test('media proxy builds an empty url when there is no recording', function () {
    expect(MediaController::proxyUrl(null))->toBe("");
    expect(MediaController::proxyUrl(""))->toBe("");
});
