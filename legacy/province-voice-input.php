<?php
require_once '_includes/functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$province_lookup_list = setting('province_lookup_list');
if (count($province_lookup_list) > 0) {?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" hints="<?php echo setting('gather_hints') ?>" input="<?php echo getInputType() ?>" numDigits="1" timeout="10" speechTimeout="auto" action="province-lookup-list-response.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>" method="GET">
        <?php
        for ($i = 0; $i < count($province_lookup_list); $i++) {?>
            <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                <?php echo word('for') ?> <?php echo $province_lookup_list[$i] ?> <?php echo getPressWord() ?> <?php echo getWordForNumber($i + 1) ?>
            </Say>
        <?php } ?>
    </Gather>
</Response>
<?php } else {?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" hints="<?php echo setting('gather_hints') ?>" input="speech" timeout="10" speechTimeout="auto" action="city-or-county-voice-input.php?SearchType=<?php echo $_REQUEST['SearchType']; ?>" method="GET">
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('please_say_the_name_of_the') ?> <?php echo word('state_or_province') ?>
        </Say>
    </Gather>
</Response>
<?php } ?>
