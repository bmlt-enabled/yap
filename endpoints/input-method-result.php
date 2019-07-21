<?php
    require_once '_includes/functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    getIvrResponse("index.php", null, getPossibleDigits('digit_map_location_search_method'));
    $locationSearchMethod = getDigitResponse('digit_map_location_search_method', 'Digits');

    if ($locationSearchMethod == SearchType::JFT) {
        ?>
        <Response>
            <Redirect method="GET">fetch-jft.php</Redirect>
        </Response>
        <?php
        exit();
    }
    $searchType = getDigitResponse('digit_map_search_type', 'SearchType');

if (has_setting('province_lookup') && json_decode(setting('province_lookup'))) {
    $action = "province-voice-input.php";
} else {
    $action = "city-or-county-voice-input.php";
}
?>
<Response>
<?php
if ($locationSearchMethod == LocationSearchMethod::VOICE) { // voice based
    ?>
    <Redirect method="GET"><?php echo $action; ?>?SearchType=<?php echo $_REQUEST['SearchType']; ?>&amp;InputMethod=<?php echo LocationSearchMethod::VOICE ?></Redirect>
    <?php
} else if ($locationSearchMethod == LocationSearchMethod::DTMF) { // dtmf based
    ?>
    <Redirect method="GET">zip-input.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>&amp;InputMethod=<?php echo LocationSearchMethod::DTMF ?></Redirect>
    <?php
}
?>
</Response>
