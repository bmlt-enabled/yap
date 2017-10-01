<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $digits = $_REQUEST['Digits'];
    $google_maps_endpoint = "http://maps.googleapis.com/maps/api/geocode/json?address=";
    $phone_number;
    $extension;

    $routes = split("\n", file_get_contents("routes.txt"));
    for ($i = 0; $i <= count($routes); $i++) {
        $route_item = split(",", $routes[$i]);
        if ($route_item[0] == $digits) {
            $phone_number = $route_item[1];
            $extension = $route_item[2];
            break;
        }
    }
?>
<Response>
    <?php if ($phone_number != "") { ?>
        <Say>Please stand by... tranferring your call.</Say>    
        <Dial>
            <Number sendDigits="<?php echo $extension ?>">
                <?php echo $phone_number ?>
            </Number>
        </Dial>
    <?php } else { ?>
        <Say>The zip code you entered is not found.</Say>
        <Redirect method="GET">zip-input.php?Digits=1</Redirect>
    <?php } ?>
</Response>