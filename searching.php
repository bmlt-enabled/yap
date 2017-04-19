<?php
    include 'vars.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $latitude = $_REQUEST['Latitude'];
    $longitude = $_REQUEST['Longitude'];
    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));
    $search_results_raw = file_get_contents($search_url);
    $search_results = json_decode($search_results_raw);
?>
<Response>
    <Say>Meeting information found, listing the top <?php echo $results_count ?> results.</Say>
<?php
    for ($i = 1; $i <= $results_count; $i++) {
        echo "<Say>Result number " . $i . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>" . $search_results[$i]->meeting_name . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>Starts at " . $search_results[$i]->start_time . " hours.</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>Meets at " . $search_results[$i]->location_street 
                . " in " . $search_results[$i]->location_municipality 
                . ", " . $search_results[$i]->location_province . "</Say>";
    }
?>
</Response>