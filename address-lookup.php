<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $address = $_REQUEST['Digits'];
    $coordinates = getCoordinatesForAddress($address);
    $day = "today";
?>
<Response>
    <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">Searching meeting information for <?php echo $day ?> in <?php echo $coordinates->location ?></Say>
    <Redirect method="GET">meeting-search.php?Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
</Response>