<?php
    include 'config.php';
    include 'functions.php';
    require_once 'vendor/autoload.php';
    use Twilio\Rest\Client;
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

    $sid = $GLOBALS['twilio_account_sid'];
    $token = $GLOBALS['twilio_auth_token'];
    $client = new Client( $sid, $token );

    $sms_messages = isset($_REQUEST['Payload']) ? json_decode(urldecode($_REQUEST["Payload"])) : [];
    $digits = getIvrResponse();

    echo "<Response>";

    if (($digits == 1 || $digits == 3) && count( $sms_messages ) > 0 ) {
        for ( $i = 0; $i < count( $sms_messages ); $i ++ ) {
            $message = $client->messages->create($_REQUEST['From'], array("from" => $_REQUEST['To'], "body" => $sms_messages[$i]));
        }
    }

    if ($digits == 2 || $digits == 3) {
        echo "<Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
    } else {
        echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . word('post_call_more_info') . "</Say>";
        echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . word('thank_you_for_calling_goodbye') . "</Say>";
    }

    echo "</Response>";
