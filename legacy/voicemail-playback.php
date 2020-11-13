<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" hints="<?php echo setting('gather_hints') ?>" input="speech" timeout="10" speechTimeout="auto" action="voicemail-playback-response.php?Caller=<?php echo $_REQUEST['Caller']?>" method="GET">
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>"><?php echo word('please_say_the_name_of_the')?> <?php echo word('city_or_county')?></Say>
    </Gather>
</Response>
