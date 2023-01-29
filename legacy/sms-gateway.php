<?php
require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$callRecord = new CallRecord();
$callRecord->callSid = $_REQUEST['SmsSid'];
$callRecord->to_number = $_REQUEST['To'];
$callRecord->from_number = $_REQUEST['From'];
$callRecord->duration = intval(0);
$callRecord->start_time = date("Y-m-d H:i:s");
$callRecord->end_time = date("Y-m-d H:i:s");
$callRecord->type = RecordType::SMS;
$callRecord->payload = json_encode($_REQUEST);

insertCallRecord($callRecord);

checkSMSBlackhole();

$address = $_REQUEST['Body'];
if (str_exists($address, ',')) {
    $coordinates = getCoordinatesForAddress($address);
} else {
    $coordinates = getCoordinatesForAddress($address . "," . getProvince());
}
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
        <Sms><?php echo word('please_send_a_message_formatting_as') ?> "<?php echo $sms_helpline_keyword?>", <?php echo word('followed_by_your_location')?>, <?php echo word('for') ?> <?php echo word('someone_to_talk_to')?>.</Sms>
    <?php }
} elseif (json_decode(setting('jft_option')) && str_exists(strtoupper($address), strtoupper('jft'))) {
    $reading_chunks = get_reading(ReadingType::JFT, true);
    for ($i = 0; $i < count($reading_chunks); $i++) {
        $GLOBALS['twilioClient']->messages->create($_REQUEST['From'], array("from" => $_REQUEST['To'], "body" => $reading_chunks[$i]));
    }
} elseif (json_decode(setting('spad_option')) && str_exists(strtoupper($address), strtoupper('spad'))) {
    $reading_chunks = get_reading(ReadingType::SPAD, true);
    for ($i = 0; $i < count($reading_chunks); $i++) {
        $GLOBALS['twilioClient']->messages->create($_REQUEST['From'], array("from" => $_REQUEST['To'], "body" => $reading_chunks[$i]));
    }
} else {
    insertCallEventRecord(EventId::MEETING_SEARCH_SMS);
    insertCallEventRecord(
        EventId::MEETING_SEARCH_LOCATION_GATHERED,
        (object)['gather' => $address, 'coordinates' => isset($coordinates) ? $coordinates : null]
    );
    ?>
    <Redirect method="GET">meeting-search.php?SearchType=<?php echo getDigitForAction('digit_map_search_type', SearchType::VOLUNTEERS)?>&amp;Latitude=<?php echo strval($coordinates->latitude) ?>&amp;Longitude=<?php echo strval($coordinates->longitude) ?></Redirect>
    <?php
}
?>
</Response>
