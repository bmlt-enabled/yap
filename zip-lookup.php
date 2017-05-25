<?php
    include 'vars.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $zip = $_REQUEST['Zip'];
    $search_type = $_REQUEST['Digits'];
	
    if (strlen($zip) > 0) {
        $map_details_response = file_get_contents($google_maps_endpoint . $zip);
        $map_details = json_decode($map_details_response);
        $location = $map_details->results[0]->formatted_address;
        $coords = $map_details->results[0]->geometry->location;
        $latitude = $coords->lat;
        $longitude = $coords->lng;
    }
?>
<Response>
    <?php if ($search_type == "1") { ?>
        <Say>Searching meeting information for <?php echo $day ?> in <?php echo $location ?></Say>
    <?php } else { ?>
        <Say>Searching meeting information for upcoming close to <?php echo $location ?></Say>
    <?php } ?>
    <Redirect method="GET">searching.php?SearchType=<?php echo $search_type ?>&amp;Latitude=<?php echo $latitude ?>&amp;Longitude=<?php echo $longitude ?></Redirect>
</Response>