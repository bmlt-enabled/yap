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

];
