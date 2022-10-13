<?php
require_once '_includes/functions.php';
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$address = getIvrResponse();
$coordinates = getCoordinatesForAddress($address);
insertCallEventRecord(
    EventId::MEETING_SEARCH_LOCATION_GATHERED,
    (object)['gather' => $address, 'coordinates' => isset($coordinates) ? $coordinates : null]
);

if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) { ?>
    <Response>
        <Redirect method="GET">input-method.php?Digits=<?php echo $_REQUEST["SearchType"] . "&amp;Retry=1"; ?></Redirect>
    </Response>
     <?php
        return;
}
?>
<Response>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>"> <?php echo word('searching_meeting_information_for')?> <?php echo $coordinates->location ?></Say>
    <Redirect method="GET">meeting-search.php?Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
</Response>
