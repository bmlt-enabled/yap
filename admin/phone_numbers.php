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
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th scope="col"><?php echo word('phone_numbers') ?></th>
                        <th scope="col"><?php echo word('name') ?></th>
                        <th scope="col">Voice Url</th>
                        <th scope="col">SMS Url</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($client->incomingPhoneNumbers->read() as $number) { ?>
                    <tr>
                        <td><?php echo $number->phoneNumber ?></td>
                        <td><?php echo $number->friendlyName ?></td>
                        <td><a target="_blank" href="<?php echo $number->voiceUrl ?>"><?php echo $number->voiceUrl ?></a></td>
                        <td><a target="_blank" href="<?php echo $number->smsUrl ?>"><?php echo $number->smsUrl ?></a></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once 'footer.php';
