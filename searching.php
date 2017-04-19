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
        $part_1 = "Result number " . $i;
        $part_2 = $search_results[$i]->meeting_name;
        $part_3 = "Starts at " . $search_results[$i]->start_time . " hours.";
        $part_4 = "Meets at " . $search_results[$i]->location_street 
                . " in " . $search_results[$i]->location_municipality 
                . ", " . $search_results[$i]->location_province;
        
        echo "<Pause length=\"1\"/>";
        echo "<Say>" . $part_1 . "</Say>";
        echo "<Say>" . $part_2 . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>" . $part_3 . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>" . $part_4 . "</Say>";
        
        $message .= $part_1 . $text_space . $part_2 . $text_space . 
                $part_3 . $text_space . $part_4 . $text_space . $text_space;
    }
    
    echo "<Sms>" . $message . "</Sms>";
    echo "<Say>Thank you for calling, goodbye.</Say>"
?>
</Response>