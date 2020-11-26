<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$province = json_decode(setting('province_lookup')) ? $_REQUEST['SpeechResult'] : "";
?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" hints="<?php echo setting('gather_hints') ?>" input="speech" timeout="10" speechTimeout="auto" action="voice-input-result.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>&amp;Province=<?php echo urlencode($province)?>" method="GET">
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>"><?php echo word('please_say_the_name_of_the')?> <?php echo word('city_or_county')?></Say>
    </Gather>
</Response>
