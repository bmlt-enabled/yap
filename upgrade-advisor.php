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

    $root_server_settings = json_decode(get(getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetServerInfo"));

    if (strpos(getHelplineBMLTRootServer(), 'index.php')) {
        echo "\n\nYour root server points to index.php. Please make sure to set it to just the root directory.";
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
