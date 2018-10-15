<?php
require_once 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<Response>
<?php if ($_REQUEST['Digits'] == "1") {?>
    <Dial>
        <Conference endConferenceOnExit="true" startConferenceOnEnter="true" beep="false">
            <?php echo $_REQUEST['conference_name'] ?>
        </Conference>
    </Dial>
<?php } else { ?>
    <Hangup/>
<?php } ?>
</Response>
