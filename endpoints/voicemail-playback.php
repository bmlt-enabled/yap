<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
// TODO: Service body ID hardcoded.
$voicemails = getVoicemail(101);
insertCallEventRecord(EventId::VOICEMAIL_PLAYBACK);

// TODO: How to determine which service body ID to use?
// TODO: Who can play voicemails play?
// TODO: How many voicemails to play?
// TODO: Date and phone number formatting

if (count($voicemails) > 0) {
    $voicemail = $voicemails[0];?>
<Response>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        <?php echo sprintf("Voicemail received at %s, from phone number %s", $voicemail['event_time'], $voicemail['from_number'])?>
    </Say>
    <Play><?php echo sprintf("%s.%s", json_decode($voicemail['meta'])->url, 'mp3')?></Play>
    <Hangup/>
</Response>
<?php } else { ?>
<Response>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        There are no recent voicemail messages to play.
    </Say>
    <Hangup/>
</Response>
<?php }
