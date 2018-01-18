<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $address = $_REQUEST['Digits'];

    $coordinates = getCoordinatesForAddress($address);
    $service_body = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);  
    
    $location = $service_body->name;
    $phone_number = explode("\|", $service_body->helpline)[0];
    $extension = explode("\|", $service_body->helpline)[1] ?: "w";
?>
<Response>
    <?php if (strpos($phone_number, 'i') !== false) { ?>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Please wait while we connect your call...</Say>
        <Enqueue waitUrl="helpline-enqueue.php?queue=<?php echo $phone_number ?>"><?php echo $phone_number ?></Enqueue>
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