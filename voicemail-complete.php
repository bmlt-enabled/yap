<?php
include 'functions.php';
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;
use PHPMailer\PHPMailer\PHPMailer;
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$serviceBodyConfiguration = getServiceBodyConfiguration(setting("service_body_id"));
$serviceBodyName = getServiceBody( setting( "service_body_id" ) )->name;

if ($serviceBodyConfiguration->primary_contact_number_enabled) {
    $sid   = $GLOBALS['twilio_account_sid'];
    $token = $GLOBALS['twilio_auth_token'];
    try {
        $client = new Client( $sid, $token );
    } catch ( \Twilio\Exceptions\ConfigurationException $e ) {
        log_debug( "Missing Twilio Credentials" );
    }

    $callerNumber = $_REQUEST["caller_number"];
    if (strpos($callerNumber, "+") !== 0) {
        $callerNumber .= "+" . $callerNumber;
    }

    $client->messages->create(
        $serviceBodyConfiguration->primary_contact_number,
        array(
            "from" => $_REQUEST["called_number"],
            "body" => "You have a message from the " . $serviceBodyName . " helpline from caller " . $callerNumber . ", " . $_REQUEST["RecordingUrl"] . ".mp3"
        )
    );
}

if ($serviceBodyConfiguration->primary_contact_email_enabled && has_setting('smtp_host')) {
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
        $recipients = explode(",", $serviceBodyConfiguration->primary_contact_email);
        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }
        $mail->addStringAttachment(file_get_contents($_REQUEST["RecordingUrl"] . ".mp3"), $_REQUEST["RecordingUrl"] . ".mp3");
        $mail->Body = "You have a message from the " . $serviceBodyName . " helpline from caller " . $_REQUEST["caller_number"] . ", " . $_REQUEST["RecordingUrl"] . ".mp3";
        $mail->Subject = 'Helpline Voicemail from ' . $serviceBodyName;
        $mail->send();
    } catch (Exception $e) {
        log_debug('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }

}
