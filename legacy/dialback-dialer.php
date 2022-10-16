<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$dialbackPin = lookupDialbackPin($_REQUEST['Digits']);
if (count($dialbackPin) > 0) {?>
    <Response>
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('please_wait_while_we_connect_your_call'); ?>
        </Say>
        <Dial callerId="<?php echo $_GET['Called'] ?>">
            <?php echo $dialbackPin[0]['from_number']; ?>
        </Dial>
    </Response>
<?php } else { ?>
    <Response>
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        Invalid pin entry
    </Say>
        <Pause length="2"/>
        <Redirect>index.php</Redirect>
    </Response>
<?php }?>
