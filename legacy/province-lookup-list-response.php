<?php
require_once '_includes/functions.php';
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$province_lookup_item = getIvrResponse(sprintf("province-voice-input.php?SearchType=%s", $_REQUEST['SearchType']), null, range(1, count(setting('province_lookup_list'))));
if ($province_lookup_item == null) {
    return;
}
insertCallEventRecord(EventId::PROVINCE_LOOKUP_LIST, (object)['province_lookup_list' => setting('province_lookup_list')[$province_lookup_item - 1]]);
?>

<Response>
    <Redirect>city-or-county-voice-input.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>&amp;SpeechResult=<?php echo urlencode(setting('province_lookup_list')[$province_lookup_item - 1]); ?></Redirect>
</Response>
