<?php
include 'config.php';
include 'functions.php';
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

$sid                        = $GLOBALS['twilio_account_sid'];
$token                      = $GLOBALS['twilio_auth_token'];
try {
    $client = new Client( $sid, $token );
} catch ( \Twilio\Exceptions\ConfigurationException $e ) {
    error_log("Missing Twilio Credentials");
}

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$conferences = $client->conferences->read( array ("friendlyName" => $_REQUEST['conference_name'] ) );
$participants = $client->conferences($conferences[0]->sid)->participants->read();?>

<Response>
<?php if (count($participants) > 0) {?>
    <Gather numDigits="1" timeout="15" action="helpline-answer-response.php?conference_name=<?php echo $_REQUEST['conference_name'] ?>" method="GET">
        <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
            You have a call from the helpline, press 1 to accept.  Press any other key to hangup.
        </Say>
    </Gather>
<?php } else { ?>
    <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
        Unfortunately the caller hung up before we could connect you.  Good bye.
    </Say>
    <Hangup/>
<?php } ?>
</Response>
