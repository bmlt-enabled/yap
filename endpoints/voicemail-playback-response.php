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
$volunteer_routing_parameters = new VolunteerRoutingParameters();
$volunteer_routing_parameters->service_body_id = $service_body_id;
$volunteers = getVolunteers($service_body_id);
$voicemail_grace_hrs = setting('voicemail_playback_grace_hours');
$caller = $_REQUEST['Caller'];
$found = false;
foreach ($volunteers as $volunteer) {
    if (strpos($caller, $volunteer->volunteer_phone_number) > 0) {
        $found = true;
        break;
    }
}
if (!$found) { ?>
    <Response>
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            access denied
        </Say>
        <Pause length="2"/>
        <Hangup/>
    </Response>
    <?php
    exit();
}
insertCallEventRecord(EventId::VOICEMAIL_PLAYBACK);
?>
<Response>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        <?php echo sprintf("Connected to %s voicemail", $location);?>
    </Say>
    <Pause length="2" />
<?php
if (count($voicemails) > 0) {
    foreach ($voicemails as $voicemail) {
        if (strtotime($voicemail['event_time']) > (new DateTime())->modify(sprintf("-%s hours", $voicemail_grace_hrs))->getTimestamp()) {?>
            <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                <?php echo sprintf("Voicemail received at %s, from phone number %s", date("l, F dS, yy, h:m A", strtotime($voicemail['event_time'])), implode(' ', str_split(str_replace('+', '', $voicemail['from_number'])))) ?>
            </Say>
            <Pause length="2"/>
            <Play><?php echo sprintf("%s.%s", json_decode($voicemail['meta'])->url, 'mp3') ?></Play>
            <Pause length="2"/>
            <?php
        }
    }
} else { ?>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        There are no recent voicemail messages to play.
    </Say>
    <Pause length="2"/>
<?php }?>
    <Hangup/>
</Response>
