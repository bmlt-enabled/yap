<?php
require_once '_includes/functions.php';
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

if (!has_setting('language_selections')) {
    echo "<Response><Say>language gateway options are not set, please refer to the documentation to utilize this feature.</Say><Hangup/></Response>";
    return;
}

$language_selection_options = explode(",", setting('language_selections'));
?>
<Response>
    <Pause length="<?php echo setting('initial_pause') ?>"></Pause>
    <Gather language="<?php echo setting('gather_language') ?>" input="<?php echo getInputType() ?>" numDigits="1" timeout="10" speechTimeout="auto" action="index.php" method="GET">
        <?php
        for ($i = 0; $i < count($language_selection_options); $i++) {
            include __DIR__.'/../lang/'.$language_selection_options[$i].'.php'
            ?>
            <Say voice="<?php echo voice(str_replace("-", "_", $language_selection_options[$i]))?>" language="<?php echo $language_selection_options[$i] ?>">
                <?php echo word('for') ?> <?php echo word('language_title') ?> <?php echo getPressWord() ?> <?php echo getWordForNumber($i + 1) ?>
            </Say>
        <?php } ?>
    </Gather>
</Response>
