<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $address = $_REQUEST['Body'];
    $coordinates = getCoordinatesForAddress($address . "," . getProvince());
?>
<Response>
<?php
    $sms_helpline_keyword = setting("sms_helpline_keyword");
    if (str_exists(strtoupper($address), strtoupper($sms_helpline_keyword))) {
        if (strlen(trim(str_replace(strtoupper($sms_helpline_keyword), "", strtoupper($address)))) > 0) {?>
            <Redirect method="GET">helpline-sms.php?OriginalCallerId=<?php echo $_REQUEST['From']?>&amp;To=<?php echo $_REQUEST['To']?>&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
<?php
        } else {
?>
        <Sms>Please send a message formatting as "<?php echo $sms_helpline_keyword?>", followed by your location as a city, county or zip code, to talk to someone.</Sms>
<?php   }
    } else {
?>
    <Redirect method="GET">meeting-search.php?SearchType=1&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
<?php
    }
?>
</Response>
