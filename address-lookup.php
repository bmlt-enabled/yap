<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    $address = $_REQUEST['Digits'];
    $coordinates = getCoordinatesForAddress($address);

    if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) { ?>
        <Response>
        <Redirect method="GET">input-method.php?Digits=<?php echo $_REQUEST["SearchType"] . "&amp;Retry=1"; ?></Redirect>
        </Response>
         <?php
        exit();
    }

    $day = "today";
?>
<Response>
    <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>"> <?php echo word('searching_meeting_information_for')?> <?php echo $day ?>, <?php echo $coordinates->location ?></Say>
    <Redirect method="GET">meeting-search.php?Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
</Response>