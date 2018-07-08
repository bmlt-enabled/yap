<?php
include 'functions.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

if (!has_setting('language_selections')) {
    echo "<Response><Say>language gateway options are not set, please refer to the documentation to utilize this feature.</Say><Hangup/></Response>";
    exit();
}

$language_selection_options = setting('language_selections');
?>
<Response>
    <Gather numDigits="1" timeout="10" action="input.php" method="GET">
        <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
            <?php echo setting('title') ?>
        </Say>
        <?php
        for ($i = 0; $i < count($language_selection_options); $i++) {
            include_once 'lang/'.$language_selection_options[$i].'.php'
            ?>
            <Say voice="alice" language="<?php echo $language_selection_options[$i] ?>">
                <?php echo word('for') ?> <?php echo word('language_title') ?> <?php echo word('press') ?> <?php echo word(getWordForNumber($i + 1)) ?>
            </Say>
        <?php }
        ?>
    </Gather>
</Response>
