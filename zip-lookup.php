<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
   
    $digits = $_REQUEST['Digits'];
	
    if (strlen($digits) > 0) {
        $map_details_response = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?address=" . $digits);
        $map_details = json_decode($map_details_response);
        $location = $map_details->results[0]->formatted_address;
    }
?>
<Response>
    <Say>Searching in <?php echo $location?></Say>
    <Redirect method="GET">searching.php</Redirect>
</Response>