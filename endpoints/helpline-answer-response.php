<?php
require_once 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
<?php if ($_REQUEST['Digits'] == "1") {
    log_debug("They took the call.");
    ?>
    <Dial>
        <Conference
            statusCallback="helpline-dialer.php?service_body_id=<?php echo $calculated_service_body_id . getConfigFileOverrideString(true) ?>"
            statusCallbackMethod="GET"
            statusCallbackEvent="join end"
            endConferenceOnExit="true"
            startConferenceOnEnter="true"
            beep="false">
            <?php echo $_REQUEST['conference_name'] ?>
        </Conference>
    </Dial>
<?php } else {
    log_debug("They rejected the call.") ?>
    <Hangup/>
<?php } ?>
</Response>
