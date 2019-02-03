<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
    <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
        <?php echo word('you_are_being_added_to_an_ongoing_call_as_muted_for_a_trainee') ?>
    </Say>
    <Dial>
        <Conference
            statusCallbackMethod="GET"
            statusCallbackEvent="join"
            muted="true"
            beep="false">
            <?php echo $_REQUEST['conference_name'] ?>
        </Conference>
    </Dial>
</Response>
