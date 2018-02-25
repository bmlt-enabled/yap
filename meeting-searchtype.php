<?php
    include 'config.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $digits = $_REQUEST['Digits'];
?>
<Response>
    <Gather numDigits="1" timeout="10" action="address-lookup.php?Address=<?php echo urlencode($digits) ?>" method="GET">
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 1 to search for meetings today near you.</Say>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Press 2 to search for meetings upcoming</Say>
    </Gather>
</Response>