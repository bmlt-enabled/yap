<?php
include 'config.php';
include 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$conference_name = $_REQUEST['conference-name'];
?>
<Response>
    <Dial>
        <Conference
            endConferenceOnExit="true"
            beep="false">
            <?php echo $conference_name ?>
        </Conference>
    </Dial>
</Response>
