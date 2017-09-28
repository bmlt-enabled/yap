<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $digits = $_REQUEST['Digits'];
?>
<Response>
    <Say>Please stand by... tranferring your call.</Say>    
    <Dial>
        <Number sendDigits="wwwww700">
            336-338-7707
        </Number>
    </Dial>
</Response>