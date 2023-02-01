<?php
require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
<?php if ($_REQUEST['Digits'] == "1") {
    $conferences = $GLOBALS['twilioClient']->conferences->read(array ("friendlyName" => $_REQUEST['conference_name'] ));
    $participants = $GLOBALS['twilioClient']->conferences($conferences[0]->sid)->participants->read();

    if (count($participants) == 2) {
        error_log("Enough volunteers have joined.  Hanging up this volunteer.") ?>
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('volunteer_has_already_joined_the_call_goodbye'); ?>
        </Say>
        <Hangup/>
        <?php
    } else {
        insertCallEventRecord(EventId::VOLUNTEER_IN_CONFERENCE, (object)["to_number" => $_REQUEST['Called']]); ?>
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
    <?php } ?>
<?php } else {
    insertCallEventRecord(
        EventId::VOLUNTEER_REJECTED,
        (object)["digits" => $_REQUEST['Digits'], "to_number" => $_REQUEST['Called']]
    );
    incrementNoAnswerCount();
    setConferenceParticipant($_REQUEST['conference_name'], CallRole::VOLUNTEER);
    log_debug("They rejected the call.") ?>
    <Hangup/>
<?php } ?>
</Response>
