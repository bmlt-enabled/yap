<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $address = $_REQUEST['Digits'];
    $coordinates = getCoordinatesForAddress($address);
    $day = "today";
?>
<Response>
    <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>"> <?php echo word('searching_meeting_information_for')?> <?php echo $day ?>, <?php echo $coordinates->location ?></Say>
    <Redirect method="GET">meeting-search.php?Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
</Response>