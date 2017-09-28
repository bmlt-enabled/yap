<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $digits = $_REQUEST['Digits'];
?>
<Response>
    <Dial>
        <Say>Please stand by... tranferring your call.</Say>
        <Number sendDigits="700">
            336-338-7707
        </Number>
    </Dial>
</Response>