<?php
    include 'config.php';


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
            $all_good = false;
        }
    }

    if ($all_good) {
        echo "Ready to Yap!";
    }
