<?php
include 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

getServiceBodyConfiguration(setting("service_body_id"));
$promptset_name = str_replace("-", "_", setting("word_language")) . "_voicemail_greeting";
?>
<Response>
    <?php if (has_setting($promptset_name)) {?>
    <Play><?php echo setting($promptset_name) ?></Play>
    <?php } else { ?>
        <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
            <?php echo word("please_leave_a_message_after_the_tone")?>, <?php echo word("hang_up_when_finished")?>
        </Say>
    <?php } ?>
    <Record
        playBeep="true"
        recordingStatusCallback="voicemail-complete.php?service_body_id=<?php echo setting("service_body_id") ?>&amp;caller_id=<?php echo urlencode($_REQUEST["caller_id"])?>&amp;caller_number=<?php echo urlencode($_REQUEST["caller_number"])?>"
        recordingStatusCallbackMethod="GET"
        maxLength="120"
        timeout="15"/>
</Response>
