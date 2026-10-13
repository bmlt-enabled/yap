<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disable Twilio Signature Validation
    |--------------------------------------------------------------------------
    |
    | Development/local ONLY escape hatch for the ValidateTwilioSignature
    | middleware. It is off by default and is only honored outside of
    | production (the middleware additionally gates on the environment), so
    | enabling it can never weaken a production deployment.
    |
    */

    'disable_signature_validation' => env('TWILIO_DISABLE_SIGNATURE_VALIDATION', false),

    /*
    |--------------------------------------------------------------------------
    | Fake Twilio Mode
    |--------------------------------------------------------------------------
    |
    | Development/CI ONLY. When enabled, TwilioService builds its REST client on
    | App\Services\FakeTwilio\FakeTwilioHttpClient, which records every outbound
    | Twilio API request in the database instead of sending it to api.twilio.com,
    | and the /fake-twilio/* inspection routes are registered for the call/SMS
    | simulator (php artisan yap:simulate). Like the signature bypass above it is
    | ignored in production, so a live helpline can never silently drop calls
    | or SMS.
    |
    */

    'fake_mode' => env('TWILIO_FAKE_MODE', false),

];
