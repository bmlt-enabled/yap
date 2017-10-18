<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $digits = $_REQUEST['Digits'];
?>
<Response>
    <Gather numDigits="1" timeout="10000" action="address-lookup.php?Address=<?php echo urlencode($digits) ?>" method="GET">
        <Say>Press 1 to search for meetings today near you.</Say>
        <Say>Press 2 to search for meetings upcoming</Say>
    </Gather>
</Response>