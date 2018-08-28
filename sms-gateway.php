<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $address = $_REQUEST['Body'];
    $coordinates = getCoordinatesForAddress($address . "," . getProvince());
?>
<Response>
<?php
    if (str_exists(strtoupper($address), "HELP")) {
        if (strlen(trim(str_replace("HELP", "", strtoupper($address)))) > 0) {?>
            <Redirect method="GET">helpline-sms.php?OriginalCallerId=<?php echo $_REQUEST['From']?>&amp;To=<?php echo $_REQUEST['To']?>&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
<?php
        } else {
?>
        <Sms>Please enter as "help" followed by your location as a city, county or zip code</Sms>
<?php   }
    } else {
?>
    <Redirect method="GET">meeting-search.php?SearchType=1&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
<?php
    }
?>
</Response>
