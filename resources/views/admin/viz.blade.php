<?php require_once __DIR__ . '/../_includes/functions.php'; ?>
    <!DOCTYPE html>
<html>
<head>
    <title>yap-viz</title>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <style>
        #map {
            height: 100%;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        input[type="button"] {
            font-size: 25px;
            font-family: Arial;
            border: 1px solid;
        }
    </style>
</head>
<body>
<div id="map"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript">
    var map, infoWindow, geocoder;
    var service_bodies = [];
    var root = "https://tomato.bmltenabled.org/main_server/";
    $(function() {
        $.getJSON(root + "client_interface/jsonp/?switcher=GetServiceBodies&callback=?", function(data) {
            service_bodies = data;
        });
        var pusher = new Pusher('<?php echo $GLOBALS['pusher_auth_key'] ?>', {
            cluster: 'us2',
            forceTLS: true
        });
        var channel = pusher.subscribe('yap-viz');
        channel.bind('helpline-search', function(data) {
            setMapInfo({
                "lat": data["latitude"],
                "lng": data["longitude"]
            }, data["location"]);
        });
    });

    function getServiceBodyForCoordinates(latitude, longitude, callback) {
        $.getJSON(root + "/client_interface/jsonp/?switcher=GetSearchResults&sort_results_by_distance=1&geo_width=-10&long_val=" + longitude + "&lat_val=" + latitude + "&callback=?", function (data) {
            callback(data);
        });
    }

    function initMap() {
        startingPos = { lat: 0, lng: 0 };
        map = new google.maps.Map(document.getElementById('map'), {
            center: startingPos,
            zoom: 10,
        });
        infoWindow = new google.maps.InfoWindow;
        getcoder = new google.maps.Geocoder;
    }

    function setMapInfo(pos, location) {
        infoWindow.setPosition(pos);
        getServiceBodyForCoordinates(pos.lat, pos.lng, function(data) {
            var serviceBodyDetails = getServiceBodyById(data[0]["service_body_bigint"]);
            var content = "<b>" + serviceBodyDetails["name"] + "</b>";
            content += "<br>Caller Location Request: " + location;
            content += "<br>Helpline: <a href='tel:" + serviceBodyDetails["helpline"].split("|")[0] + "' target='_blank'>" + serviceBodyDetails["helpline"].split("|")[0] + "</a>";
            infoWindow.setContent(content);
            infoWindow.open(map);
            map.setCenter(pos);
        });
    }

    function getServiceBodyById(id) {
        for (var service_body of service_bodies) {
            if (service_body["id"] === id) return service_body;
        }
    }
</script>
<script async defer
        src="https://maps.googleapis.com/maps/api/js?key=<?php echo $GLOBALS['viz_google_maps_api_key']?>&callback=initMap">
</script>
<script src="https://js.pusher.com/4.3/pusher.min.js"></script>
</body>
</html>
