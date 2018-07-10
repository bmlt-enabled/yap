<?php
    include 'config.php';
    include 'functions.php';


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
            echo "Missing required setting: " . $setting . "<br/>";
            exit();
        }
    }

    $googleapi_setttings = json_decode(get("https://maps.googleapis.com/maps/api/geocode/json?key=" .$google_maps_api_key. "&address=91409"));

    $root_server_settings = json_decode(get(getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetServerInfo"));

    if ($googleapi_setttings->status == "REQUEST_DENIED") {
        echo "Your Google Maps API key came back with the following error. " .$googleapi_setttings->error_message. " Please make sure you have the \"Google Maps Geocoding API\" enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console <a target=\"_blank\" href=\"https://console.cloud.google.com/apis/\">here</a>";
        exit();
    }

    if (strpos(getHelplineBMLTRootServer(), 'index.php')) {
        echo "Your root server points to index.php. Please make sure to set it to just the root directory.";
        exit();
    }

    if (!isset($root_server_settings)) {
        echo "Your root server returned no server information.  Double-check that you have the right root server url.";
        exit();
    }

    if ($root_server_settings[0]->semanticAdmin != "1") {
        echo "Semantic Admin not enabled on your root server, be sure to set the variable mentioned <a target=\"_blank\" href=\"https://bmlt.magshare.net/semantic/semantic-administration/\">here</a>.";
        exit();
    }

    if ($all_good) {
        echo "Ready to Yap!";
    }
