<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $zip = $_REQUEST['Zip'];
    $search_type = $_REQUEST['Digits'];
    $coordinates = getCoordinatesForZipCode($zip);
?>
<Response>
    <?php if ($search_type == "1") { ?>
        <Say>Searching meeting information for <?php echo $day ?> in <?php echo $coordinates->location ?></Say>
    <?php } else { ?>
        <Say>Searching meeting information for upcoming close to <?php echo $coordinates->location ?></Say>
    <?php } ?>
    <Redirect method="GET">meeting-search.php?SearchType=<?php echo $search_type ?>&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
</Response>