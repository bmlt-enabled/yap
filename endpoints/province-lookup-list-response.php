<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$province_lookup_item = intval($_REQUEST['Digits']);?>
<Response>
    <Redirect>city-or-county-voice-input.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>&amp;SpeechResult=<?php echo urlencode(setting('province_lookup_list')[$province_lookup_item - 1]); ?></Redirect>
</Response>
