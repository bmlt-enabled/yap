<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $zip = $_REQUEST['Digits'];

    $coordinates = getCoordinatesForAddress($zip);
    $service_body = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);
    
    $location = $service_body->name;
    $phone_number = split("\|", $service_body->helpline)[0];
    $extension = split("\|", $service_body->helpline)[1] ?: "w";
?>
<Response Location="<?php echo $location; ?>">
    <?php if ($phone_number != "") { ?>
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