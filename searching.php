<?php
    include 'vars.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $latitude = $_REQUEST['Latitude'];
    $longitude = $_REQUEST['Longitude'];
    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));
    $search_results_raw = file_get_contents($search_url);
    $search_results = json_decode($search_results_raw);
    $message = "";
?>
<Response>
    <Say>Meeting information found, listing the top <?php echo $results_count ?> results.</Say>
<?php
    for ($i = 1; $i <= $results_count; $i++) {
        $part_1 = $search_results[$i]->meeting_name;
        $part_2 = $search_results[$i]->start_time;
        $part_3 = $search_results[$i]->location_street 
                . " in " . $search_results[$i]->location_municipality 
                . ", " . $search_results[$i]->location_province;
        
        echo "<Pause length=\"1\"/>";
        echo "<Say>Result number " . $i . "</Say>";
        echo "<Say>" . $part_1 . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>Starts at " . $part_2 . " hours.</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>" . $part_3 . "</Say>";
        
        $message .= $part_1 . $text_space . $part_2 . $text_space . $part_3;
    }
    
    echo "<Sms>" . $message . "</Sms>";
    echo "<Say>Thank you for calling, goodbye.</Say>"
?>
</Response>