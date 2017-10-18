<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $address = $_REQUEST['Digits'];

    $coordinates = getCoordinatesForAddress($address);
    $service_body = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);
    
    $location = $service_body->name;
    $phone_number = split("\|", $service_body->helpline)[0];
    $extension = split("\|", $service_body->helpline)[1] ?: "w";
    
    header("x-yap-location: " . $location);
?>
<Response>
    <?php if (strpos($phone_number, 'i') !== false) { ?>
        <Say>Please wait while we connect your call...</Say>
        <Enqueue waitUrl="helpline-enqueue.php?queue=<?php echo $phone_number ?>"><?php echo $phone_number ?></Enqueue>
    <?php } else if ($phone_number != "") { ?>
        <Say>Please stand by... relocating your call to <?php echo $location; ?>.</Say>    
        <Dial>
            <Number sendDigits="<?php echo $extension ?>">
                <?php echo $phone_number ?>
            </Number>
        </Dial>
    <?php } else { ?>
        <Say>The zip code you entered is not found.</Say>
        <Redirect method="GET">zip-input.php?Digits=1</Redirect>
    <?php } ?>
</Response>