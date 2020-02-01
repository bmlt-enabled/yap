<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$address = isset($_REQUEST['SpeechResult']) ? $_REQUEST['SpeechResult'] : getIvrResponse();
$province = has_setting('province_lookup') && json_decode(setting('province_lookup')) ? $_REQUEST['Province'] : getProvince();
$coordinates  = getCoordinatesForAddress(sprintf("%s, %s", $address, $province));
$service_body_obj = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);
$service_body_id = isset($service_body_obj) ? $service_body_obj->id : 0;
$location = $service_body_obj->name;
$voicemails = getVoicemail($service_body_id);
insertCallEventRecord(EventId::VOICEMAIL_PLAYBACK);

// TODO: Who can play voicemails play?
// TODO: How many voicemails to play? (last 48 hours)
// TODO: Date and phone number formatting
?>
<Response>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        <?php echo sprintf("Connected to %s voicemail", $location);?>
    </Say>
<?php
if (count($voicemails) > 0) {
    $voicemail = $voicemails[0];?>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        <?php echo sprintf("Voicemail received at %s, from phone number %s", $voicemail['event_time'], $voicemail['from_number'])?>
    </Say>
    <Play><?php echo sprintf("%s.%s", json_decode($voicemail['meta'])->url, 'mp3')?></Play>
<?php } else { ?>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        There are no recent voicemail messages to play.
    </Say>
<?php }?>
    <Hangup/>
</Response>
