<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $tracker = isset($_REQUEST["tracker"]) ? intval($_REQUEST["tracker"]) + 1 : 0;
    $service_body_id = $_REQUEST["service_body_id"];
    $phone_number = getHelplineVolunteer($service_body_id, $tracker);
    $call_status = isset($_REQUEST["DialCallStatus"]) ? $_REQUEST["DialCallStatus"] : "starting";
    $call_timeout = isset($GLOBALS["call_timeout"]) ? $GLOBALS["call_timeout"] : 20;
?>
<Response>
<?php
    if ($call_status != "completed") { ?>
        <Dial method="GET"
          timeout="<?php echo $call_timeout?>"
          callerId="<?php
          if (isset($GLOBALS['outbound_callerid']) && $GLOBALS['outbound_callerid']) { 
            echo isset($GLOBALS["outbound_callerid"]) ? $GLOBALS["outbound_callerid"] : "0000000000"; 
          } else { 
              echo isset($_REQUEST["Called"]) ? $_REQUEST["Called"] : "0000000000";  
          } ?>"
          action="helpline-dialer.php?service_body_id=<?php echo $service_body_id; ?>&amp;tracker=<?php echo $tracker; ?>&amp;Called=<?php echo urlencode(isset($_REQUEST["Called"]) ? $_REQUEST["Called"] : "0000000000") ?>">
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
