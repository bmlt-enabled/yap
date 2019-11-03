<?php
require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
<?php if ($_REQUEST['Digits'] == "1") {
    log_debug("They took the call."); ?>
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
    $conferences = $twilioClient->conferences->read(array ("friendlyName" => $_REQUEST['conference_name'] ));
    insertCallEventRecord(EventId::VOLUNTEER_REJECTED,
        (object)["digits" => $_REQUEST['Digits']]
    );
    setConferenceParticipant($conferences[0]->sid, $_REQUEST['conference_name']);
    log_debug("They rejected the call.") ?>
    <Hangup/>
<?php } ?>
</Response>
