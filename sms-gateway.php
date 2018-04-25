<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $address = $_REQUEST['Body'];
    $coordinates = getCoordinatesForAddress($address . "," . getProvince());
?>
<Response>
    <Redirect method="GET">meeting-search.php?SearchType=1&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
</Response>
