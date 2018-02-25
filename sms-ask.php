<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

    $sms_messages = json_decode(urldecode($_REQUEST["Payload"]));

    echo "<Response>";
    for ($i = 0; $i < count($sms_messages); $i++) {
        echo $sms_messages[$i];
    }

    echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">Thank you for calling, goodbye.</Say></Response>";