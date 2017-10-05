<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $digits = $_REQUEST['Digits'];
    $google_maps_endpoint = "http://maps.googleapis.com/maps/api/geocode/json?address=";
    $phone_number;
    $extension;
    $location;

    $routes = split("\n", file_get_contents("routes.txt"));
    for ($i = 0; $i <= count($routes); $i++) {
        $route_item = split("\|", $routes[$i]);
        $zipcodes = split(",", $route_item[1]);
        for ($j = 0; $j <= count($zipcodes); $j++) {
            if ($zipcodes[$j] == $digits) {
                $location = $route_item[0];
                $phone_number = $route_item[2];
                $extension = $route_item[3];
                break;
            }
        }
    }
?>
<Response>
    <?php if ($phone_number != "") { ?>
        <Say>Please stand by... tranferring your call...</Say>    
        <Dial targetLocation="<?php echo $location ?>">
            <Number sendDigits="<?php echo $extension ?>">
                <?php echo $phone_number ?>
            </Number>
        </Dial>
    <?php } else { ?>
        <Say>The zip code you entered is not found.</Say>
        <Redirect method="GET">zip-input.php?Digits=1</Redirect>
    <?php } ?>
</Response>