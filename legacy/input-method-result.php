<?php
    require_once '_includes/functions.php';
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $response = getIvrResponse("index.php", null, getPossibleDigits('digit_map_location_search_method'));
if ($response == null) {
    return;
}
    $locationSearchMethod = getDigitResponse('digit_map_location_search_method', 'Digits');

if ($locationSearchMethod == SearchType::JFT) {?>
        <Response>
            <Redirect method="GET">fetch-jft.php</Redirect>
        </Response>
    <?php
    return;
}

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
} elseif ($locationSearchMethod == LocationSearchMethod::DTMF) { // dtmf based
    ?>
    <Redirect method="GET">zip-input.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>&amp;InputMethod=<?php echo LocationSearchMethod::DTMF ?></Redirect>
    <?php
}
?>
</Response>
