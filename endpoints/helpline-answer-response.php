<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
<?php if ($_REQUEST['Digits'] == "1") {
    log_debug("They took the call.");
    insertCallEventRecord(EventId::VOLUNTEER_IN_CONFERENCE, (object)["to_number" => $_REQUEST['Called']]);
    ?>
    <Dial>
        <Conference
            statusCallbackMethod="GET"
            statusCallbackEvent="join"
            endConferenceOnExit="true"
            startConferenceOnEnter="true"
            beep="false">
            <?php echo $_REQUEST['conference_name'] ?>
        </Conference>
    </Dial>
<?php } else {
    insertCallEventRecord(EventId::VOLUNTEER_REJECTED, (object)["digits" => $_REQUEST['Digits']]);
    setConferenceParticipant($_REQUEST['conference_name'], CallRole::VOLUNTEER);
    log_debug("They rejected the call.") ?>
    <Hangup/>
<?php } ?>
</Response>
