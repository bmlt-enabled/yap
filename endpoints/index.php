<?php
    require_once '_includes/functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    log_debug("version: " . $GLOBALS['version']);
if (strlen(setting('language_selections')) > 0) {
    if (!isset($_REQUEST["Digits"])) {?>
            <Response><Redirect>lng-selector.php</Redirect></Response>
        <?php
        exit();
    } else {
        $selected_language = explode(",", setting('language_selections'))[intval($_REQUEST["Digits"]) - 1];
        $_SESSION["override_word_language"] = $selected_language;
        $_SESSION["override_gather_language"] = $selected_language;
        $_SESSION["override_language"] = $selected_language;
        include_once __DIR__.'/../lang/'.getWordLanguage().'.php';
    }
}

if (isset($_REQUEST["override_service_body_id"])) {
    getServiceBodyCallHandling($_REQUEST["override_service_body_id"]);
}

    $promptset_name = str_replace("-", "_", getWordLanguage()) . "_greeting";
?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" input="<?php echo getInputType() ?>" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">
        <Pause length="<?php echo setting('initial_pause') ?>"></Pause>
        <?php if (has_setting($promptset_name)) {?>
            <Play><?php echo setting($promptset_name) ?></Play>
        <?php } else { ?>
            <?php if (!isset($_REQUEST["Digits"])) { ?>
                <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                    <?php echo setting('title') ?>
                </Say>
            <?php } ?>
            <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                <?php echo getPressWord() . " " . word('one') . " " . word('to_find') . " " . word('someone_to_talk_to') ?>
            </Say>
            <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                <?php echo getPressWord() . " " . word('two') . " " . word('to_search_for') . " " . word('meetings') ?>
            </Say>
            <?php
            if (has_setting('jft_option') && json_decode(setting('jft_option'))) { ?>
                <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                    <?php echo getPressWord() . " " . word('three') . " " . word('to_listen_to_the_just_for_today') ?>
                </Say>
            <?php }
        }?>
    </Gather>
</Response>
