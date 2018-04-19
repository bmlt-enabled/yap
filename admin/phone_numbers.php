<?php
include_once 'nav.php';
require_once '../vendor/autoload.php';
use Twilio\Rest\Client;

$sid    = $GLOBALS['twilio_account_sid'];
$token  = $GLOBALS['twilio_auth_token'];
$client = new Client( $sid, $token );
?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">Voice Url</th>
                        <th scope="col">SMS Url</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    foreach ($client->incomingPhoneNumbers->read() as $number) {
                ?>
                    <tr>
                        <td><?php echo $number->phoneNumber ?></td>
                        <td><?php echo $number->friendlyName ?></td>
                        <td><?php echo $number->voiceUrl ?></td>
                        <td><?php echo $number->smsUrl ?></td>
                    </tr>
                <?php
                    }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once 'footer.php';
