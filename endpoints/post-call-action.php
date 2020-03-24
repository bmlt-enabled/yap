<?php
    require_once '_includes/functions.php';
    require_once '_includes/twilio-client.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

    $sms_messages = isset($_REQUEST['Payload']) ? json_decode(urldecode($_REQUEST["Payload"])) : [];
    $digits = getIvrResponse();

    echo "<Response>";

if (($digits == 1 || $digits == 3) && count($sms_messages) > 0) {
    if (setting("sms_combine")) {
        $message = $twilioClient->messages->create($_REQUEST['From'], array("from" => $_REQUEST['To'], "body" => implode("\n\n", $sms_messages)));
    } else {
        for ($i = 0; $i < count($sms_messages); $i++) {
            $message = $twilioClient->messages->create($_REQUEST['From'], array("from" => $_REQUEST['To'], "body" => $sms_messages[$i]));
        }
    }
}

if ($digits == 2 || $digits == 3) {
    echo "<Redirect method=\"GET\">index.php</Redirect>";
} else {
    echo "<Say voice=\"" . voice() . "\" language=\"" . setting("language") . "\">" . word('thank_you_for_calling_goodbye') . "</Say>";
}

    echo "</Response>";
