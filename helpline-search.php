<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $address = $_REQUEST['Digits'];

    $coordinates = getCoordinatesForAddress($address);
    $service_body = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);  
    
    $location = $service_body->name;
    $exploded_result = explode("\|", $service_body->helpline);
    $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
    $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
?>
<Response>
    <?php if (strpos($phone_number, 'yap') !== false) { ?>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Please wait while we connect your call...</Say>
        <Redirect method="GET">helpline-dialer.php?service_body_id=<?php echo $service_body->id ?></Redirect>
    <?php } else if ($phone_number != "") { ?>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Please stand by... relocating your call to <?php echo $location; ?>.</Say>    
        <Dial>
            <Number sendDigits="<?php echo $extension ?>"><?php echo $phone_number ?></Number>
        </Dial>
    <?php } else { ?>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">The location you entered is not found.</Say>
        <Redirect method="GET">zip-input.php?Digits=1</Redirect>
    <?php } ?>
</Response>