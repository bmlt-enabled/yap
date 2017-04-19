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
    }
?>
</Response>