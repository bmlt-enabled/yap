<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $format_id = isset($_REQUEST["format_id"]) ? $_REQUEST["format_id"] : getFormat("HV");
    $tracker = isset($_REQUEST["tracker"]) ? intval($_REQUEST["tracker"]) + 1 : 0;
    $service_body_id = $_REQUEST["service_body_id"];
    $phone_number = getHelplineVolunteer($service_body_id, $format_id, $tracker);
    $call_status = isset($_REQUEST["DialCallStatus"]) ? $_REQUEST["DialCallStatus"] : "starting";
?>
<Response>
<?php
    if ($call_status != "completed") { ?>
        <Dial method="GET"
              action="helpline-dialer.php?service_body_id=<?php echo $service_body_id; ?>&amp;format_id=<?php echo $format_id; ?>&amp;tracker=<?php echo $tracker; ?>">
            <Number>
                <?php echo $phone_number; ?>
            </Number>
        </Dial>
<?php
    } else {
        echo "<Hangup />";
    }
    ?>
</Response>
