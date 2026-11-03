<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->utility = setupTwilioService();
    $this->recordingUrl = "https://api.twilio.com/2010-04-01/Accounts/AC222a79bf52fdc8c3cf463b2846582b83/Recordings/RE123";
});

test('media proxy streams the recording with twilio basic auth for a valid signed url', function () {
    Http::fake([
        "api.twilio.com/*" => Http::response("FAKEMP3BYTES", 200, ["Content-Type" => "audio/mpeg"]),
    ]);

    $response = $this->get(MediaController::proxyUrl($this->recordingUrl));

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "audio/mpeg");
    expect($response->getContent())->toBe("FAKEMP3BYTES");

    // Fetches the .mp3 rendition from Twilio, authenticated server-side.
    Http::assertSent(function ($request) {
        return $request->url() === $this->recordingUrl . ".mp3"
            && str_starts_with($request->header("Authorization")[0] ?? "", "Basic ");
    });
});

test('media proxy does not require a twilio signature', function () {
    // /media is opened by browsers and email clients, never by Twilio, so the
    // twilio.signature middleware must not apply to it.
    Http::fake([
        "api.twilio.com/*" => Http::response("FAKEMP3BYTES", 200),
    ]);
    $this->utility->settings->set("twilio_auth_token", "testtoken");

    $this->get(MediaController::proxyUrl($this->recordingUrl))->assertStatus(200);
});

test('media proxy rejects an unsigned request', function () {
    Http::fake();

    $this->get("/media?recording=" . urlencode($this->recordingUrl))->assertStatus(403);
    Http::assertNothingSent();
});

test('media proxy rejects a tampered signed url', function () {
    Http::fake();

    // Swap the recording out from under a signature generated for a different one.
    $tamperedUrl = str_replace("RE123", "RE999", MediaController::proxyUrl($this->recordingUrl));

    $this->get($tamperedUrl)->assertStatus(403);
    Http::assertNothingSent();
});

test('media proxy rejects an expired signed url', function () {
    Http::fake();

    $expiredUrl = URL::temporarySignedRoute("media", now()->subMinute(), ["recording" => $this->recordingUrl]);

    $this->get($expiredUrl)->assertStatus(403);
    Http::assertNothingSent();
});

test('media proxy rejects a non-twilio host even when the signature is valid', function ($url) {
    Http::fake();

    $this->get(MediaController::proxyUrl($url))->assertStatus(403);
    Http::assertNothingSent();
})->with([
    "https://evil.example.com/Recordings/RE123",
    "https://api.twilio.com.evil.example.com/Recordings/RE123",
    "http://api.twilio.com/2010-04-01/Accounts/AC222a79bf52fdc8c3cf463b2846582b83/Recordings/RE123",
]);

test('media proxy returns a bad gateway when the twilio fetch fails', function () {
    Http::fake([
        "api.twilio.com/*" => Http::response("Unauthorized", 401),
    ]);

    $this->get(MediaController::proxyUrl($this->recordingUrl))->assertStatus(502);
});

test('media proxy builds an empty url when there is no recording', function () {
    expect(MediaController::proxyUrl(null))->toBe("");
    expect(MediaController::proxyUrl(""))->toBe("");
});
