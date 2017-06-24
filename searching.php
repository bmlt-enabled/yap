<?php
    include 'vars.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    
    $latitude = $_REQUEST['Latitude'];
    $longitude = $_REQUEST['Longitude'];
    $search_type_key = $bmlt_option_sort_key[intval($_REQUEST['SearchType']) - 1];
    $search_url = str_replace("{SORT_KEY}", $search_type_key,
                    str_replace("{LONGITUDE}", $longitude, 
                    str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint)));
    $search_results_raw = file_get_contents($search_url);
    $search_results = json_decode($search_results_raw);
    $message = "";
?>
<Response>
    <!--<Debug><?php echo "<![CDATA[".$search_url."]]>" ?></Debug>-->
    <Say>Meeting information found, listing the top <?php echo $results_count ?> results.</Say>
<?php
    for ($i = 0; $i < $results_count; $i++) {
        $result_day = $search_results[$i]->weekday_tinyint;
        $result_time = strtotime($search_results[$i]->start_time);
        
        $part_1 = $search_results[$i]->meeting_name;
        $part_2 = $days_of_the_week[$result_day]
                . ' ' . date('g:i A', $result_time);
        $part_3 = $search_results[$i]->location_street 
                . " in " . $search_results[$i]->location_municipality 
                . ", " . $search_results[$i]->location_province;

        echo "<Pause length=\"1\"/>";
        echo "<Say>Result number " . ($i + 1) . "</Say>";
        echo "<Say>" . $part_1 . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>Starts at " . $part_2 . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>" . $part_3 . "</Say>";
        
        $message = $part_1 . $text_space . $part_2 . $text_space . $part_3;
        echo "<Sms>" . $message . "</Sms>";
    }
    
    echo "<Say>Thank you for calling, goodbye.</Say>"
?>
</Response>