<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

    $sms_messages = isset($_REQUEST['Payload']) ? json_decode(urldecode($_REQUEST["Payload"])) : [];
    $digits = $_REQUEST['Digits'];

    echo "<Response>";

    if (($digits == 1 || $digits == 3) && count( $sms_messages ) > 0 ) {
        for ( $i = 0; $i < count( $sms_messages ); $i ++ ) {
            echo $sms_messages[ $i ];
        }
    }

    if ($digits == 2 || $digits == 3) {
        echo "<Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
    } else {
        echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . word('thank_you_for_calling_goodbye') . "</Say>";
    }

    echo "</Response>";