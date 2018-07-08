<?php
include 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
        <?php echo word("please_leave_a_message_after_the_tone")?>, <?php echo word("hang_up_when_finished")?>
    </Say>
    <Record
        playBeep="true"
        recordingStatusCallback="voicemail-complete.php?service_body_id=<?php echo $_REQUEST["service_body_id"] ?>&amp;caller_id=<?php echo urlencode($_REQUEST["caller_id"])?>&amp;caller_number=<?php echo urlencode($_REQUEST["caller_number"])?>"
        recordingStatusCallbackMethod="GET"
        maxLength="120"
        timeout="15"/>
</Response>
