<?php
include 'config.php';
include 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

?>
<Response>
    <Gather numDigits="1" timeout="15" action="helpline-answer-response.php?conference_name=<?php echo $_REQUEST['conference_name'] ?>" method="GET">
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            You have a call from the helpline, press 1 to accept.  Press any other key to hangup.
        </Say>
    </Gather>
</Response>
