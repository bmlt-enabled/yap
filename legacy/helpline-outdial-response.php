<?php
require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$conferences = $twilioClient->conferences->read(array ("friendlyName" => $_REQUEST['conference_name'] ));
$participants = $twilioClient->conferences($conferences[0]->sid)->participants->read();?>

<Response>
<?php
if (count($participants) == 2) {
    setConferenceParticipant($_REQUEST['conference_name'], CallRole::VOLUNTEER);
    error_log("Enough volunteers have joined.  Hanging up this volunteer.") ?>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        <?php echo word('volunteer_has_already_joined_the_call_goodbye'); ?>
    </Say>
    <Hangup/>
<?php } else if (count($participants) > 0) {
    insertCallEventRecord(EventId::VOLUNTEER_ANSWERED, (object)['to_number' => $_REQUEST['Called']]);
    setConferenceParticipant($_REQUEST['conference_name'], CallRole::VOLUNTEER);
    error_log("Volunteer picked up or put to their voicemail, asking if they want to take the call, timing out after 15 seconds of no response.") ?>
    <?php if (has_setting('volunteer_auto_answer') && setting('volunteer_auto_answer')) { ?>
        <Redirect method="GET">helpline-answer-response.php?Digits=1&amp;conference_name=<?php echo $_REQUEST['conference_name'] ?>&amp;service_body_id=<?php echo $_REQUEST['service_body_id'] . getSessionLink(true)?></Redirect>
    <?php } else { ?>
    <Gather actionOnEmptyResult="true" numDigits="1" timeout="15" action="helpline-answer-response.php?conference_name=<?php echo $_REQUEST['conference_name']?>&amp;service_body_id=<?php echo $_REQUEST['service_body_id'] . getSessionLink(true)?>" method="GET">
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('you_have_a_call_from_the_helpline') ?>
        </Say>
    </Gather>
    <?php } ?>
<?php } else {
    setConferenceParticipant($_REQUEST['conference_name'], CallRole::CALLER);
    insertCallEventRecord(EventID::VOLUNTEER_ANSWERED_BUT_CALLER_HUP, (object)['to_number' => $_REQUEST['Called']]);
    error_log("The caller hungup.") ?>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        <?php echo word('the_caller_hungup') ?>
    </Say>
    <Hangup/>
<?php } ?>
</Response>
