<?php
    include 'config.php';
    include 'functions.php';

    function status($state, $message) {
        header( "Content-Type: application/json" );
        echo "{\"status\":" . ($state ? "true" : "false") . ",\"message\":\"".$message."\"}";
        exit();
    }

    $all_good = true;
    $settings = [
        'title',
        'bmlt_root_server',
        'google_maps_api_key',
        'twilio_account_sid',
        'twilio_auth_token',
        'bmlt_username',
        'bmlt_password',
    ];

    function isThere($setting) {
        return isset($GLOBALS[$setting]) && strlen($GLOBALS[$setting]) > 0;
    }

    foreach ($settings as $setting) {
        if (!isThere($setting)) {
            status(false, "Missing required setting: " . $setting);
        }
    }

    $root_server_settings = json_decode(get(getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetServerInfo"));

    if (strpos(getHelplineBMLTRootServer(), 'index.php')) {
        status(false,"Your root server points to index.php. Please make sure to set it to just the root directory.");
    }

    if (!isset($root_server_settings)) {
        status(false, "Your root server returned no server information.  Double-check that you have the right root server url.");
    }

    if ($root_server_settings[0]->semanticAdmin != "1") {
        status(false, "Semantic Admin not enabled on your root server, be sure to set the variable mentioned here: https://bmlt.magshare.net/semantic/semantic-administration.");
    }

    $googleapi_setttings = json_decode(get($google_maps_endpoint . "&address=91409"));

    if ($googleapi_setttings->status == "REQUEST_DENIED") {
        status(false, "Your Google Maps API key came back with the following error. " .$googleapi_setttings->error_message. " Please make sure you have the 'Google Maps Geocoding API' enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/");
    }

    if ($all_good) {
        status(true, "Ready To Yap!");
    }
