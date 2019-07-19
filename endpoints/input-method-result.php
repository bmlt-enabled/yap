<?php
    require_once '_includes/functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $searchType = getSearchType('SearchType');
    $inputMethod = getIvrResponse("input-method.php", $_REQUEST['Digits'], ["1", "2", "3"]);

if ($inputMethod == "3" && has_setting('jft_option') && json_decode(setting('jft_option'))) {
    ?>
        <Response>
            <Redirect method="GET">fetch-jft.php</Redirect>
        </Response>
    <?php
    exit();
}
if (has_setting('province_lookup') && json_decode(setting('province_lookup'))) {
    $action = "province-voice-input.php";
} else {
    $action = "city-or-county-voice-input.php";
}
?>
<Response>
<?php
if ($inputMethod == "1") { // city or county
    ?>
    <Redirect method="GET"><?php echo $action; ?>?SearchType=<?php echo $_REQUEST['SearchType']; ?>&amp;InputMethod=<?php echo $inputMethod; ?></Redirect>
    <?php
} else { // zip
    ?>
    <Redirect method="GET">zip-input.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>&amp;InputMethod=<?php echo $inputMethod; ?></Redirect>
    <?php
}
?>
</Response>
