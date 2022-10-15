<?php
require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';
use PHPMailer\PHPMailer\PHPMailer;

insertCallEventRecord(
    EventId::VOICEMAIL,
    (object)['url' => isset($_REQUEST['RecordingUrl']) ? $_REQUEST['RecordingUrl'] : null]
);
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

function sendSmsForVoicemail($recipients, $serviceBodyCallHandling, $serviceBodyName, $callerNumber)
{
    $caller_id = getOutboundDialingCallerId($serviceBodyCallHandling);
    $dialbackString = getDialbackString($_REQUEST['CallSid'], $caller_id);

    foreach ($recipients as $recipient) {
        $GLOBALS['twilioClient']->messages->create(
            $recipient,
            array(
                "from" => $caller_id,
                "body" => sprintf("You have a message from the %s helpline from caller %s. Voicemail Link %s.mp3. %s", $serviceBodyName, $callerNumber, $_REQUEST["RecordingUrl"], $dialbackString)
            )
        );
    }
}

function sendEmailForVoicemail($recipients, $serviceBodyName, $callerNumber)
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = setting('smtp_host');
        $mail->SMTPAuth = true;
        $mail->Username = setting('smtp_username');
        $mail->Password = setting('smtp_password');
        $mail->SMTPSecure = setting('smtp_secure');
        if (has_setting('smtp_alt_port')) {
            $mail->Port = setting('smtp_alt_port');
        } elseif (setting('smtp_secure') == 'tls') {
            $mail->Port = 587;
        } elseif (setting('smtp_secure') == 'ssl') {
            $mail->Port = 465;
        }
        $mail->setFrom(setting('smtp_from_address'), setting('smtp_from_name'));
        $mail->isHTML(true);
        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }
        $mail->addStringAttachment(file_get_contents($_REQUEST["RecordingUrl"] . ".mp3"), $_REQUEST["RecordingUrl"] . ".mp3");
        $mail->Body = "You have a message from the " . $serviceBodyName . " helpline from caller " . $callerNumber . ", " . $_REQUEST["RecordingUrl"] . ".mp3";
        $mail->Subject = 'Helpline Voicemail from ' . $serviceBodyName;
        $mail->send();
    } catch (Exception $e) {
        log_debug('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }
}

hup($_REQUEST['CallSid']);

$serviceBodyCallHandling = getServiceBodyCallHandling(setting("service_body_id"));
$serviceBodyName = getServiceBody(setting("service_body_id"))->name;
$callerNumber = $_REQUEST["caller_number"];
if (strpos(trim($callerNumber), "+") !== 0) {
    $callerNumber = "+" . trim($callerNumber);
}

if ($serviceBodyCallHandling->primary_contact_number_enabled) {
    $recipients = explode(",", $serviceBodyCallHandling->primary_contact_number);
    sendSmsForVoicemail($recipients, $serviceBodyCallHandling, $serviceBodyName, $callerNumber);
}

if (isset($_SESSION["volunteer_routing_parameters"])) {
    $volunteer_routing_options = $_SESSION["volunteer_routing_parameters"];
    $volunteer_routing_options->volunteer_responder = VolunteerResponderOption::ENABLED;
    $volunteer_routing_options->volunteer_shadow = VolunteerShadowOption::UNSPECIFIED;
    $volunteers = getHelplineVolunteersActiveNow($volunteer_routing_options);
    $recipients = [];
    foreach ($volunteers as $volunteer) {
        array_push($recipients, $volunteer->contact);
    }
    if (count($volunteers) > 0) {
        sendSmsForVoicemail($recipients, $serviceBodyCallHandling, $serviceBodyName, $callerNumber);
    }
}

if ($serviceBodyCallHandling->primary_contact_email_enabled && has_setting('smtp_host')) {
    $recipients = explode(",", $serviceBodyCallHandling->primary_contact_email);
    sendEmailForVoicemail($recipients, $serviceBodyName, $callerNumber);
}
?>
<Response></Response>
