<?php
    include 'vars.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $digits = $_REQUEST['Digits'];
	
    if (strlen($digits) > 0) {
        $map_details_response = file_get_contents($google_maps_endpoint . $digits);
        $map_details = json_decode($map_details_response);
        $location = $map_details->results[0]->formatted_address;
        $coords = $map_details->results[0]->geometry->location;
        $latitude = $coords->lat;
        $longitude = $coords->lng;
    }
?>
<Response>
    <Say>Searching meeting information for <?php echo $day ?> in <?php echo $location ?></Say>
    <Redirect method="GET">searching.php?Latitude=<?php echo $latitude ?>&amp;Longitude=<?php echo $longitude ?></Redirect>
</Response>