<?php

    // TODO: Figure out timezone from ANI
    date_default_timezone_set('UTC');
    $day = date("l");
    $google_maps_endpoint = "http://maps.googleapis.com/maps/api/geocode/json?address=";
    
    // TODO: Potentially allow different BMLT roots
    $bmlt_root_server = "http://bmlt-aggregator.archsearch.org/c81e728d9d4c2f636f067f89cc14862c/bmltfed/main_server";
    $bmlt_search_endpoint = $bmlt_root_server . "/client_interface/json/index.php?switcher=GetSearchResults&sort_key=distance_in_miles,start_time&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-10&weekdays[]=" . $day;

    $results_count = 3;