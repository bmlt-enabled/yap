<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    if (strlen(setting('language_selections')) > 0) {
        if (!isset($_REQUEST["Digits"])) {
            header( 'Location: /language-selector.php' );
            exit();
        } else {
            $_SESSION["override_word_language"] = explode(",", setting('language_selections'))[intval($_REQUEST["Digits"]) - 1];
            include_once 'lang/'.setting('word_language').'.php';
        }
    }
?>
<Response>
    <Gather numDigits="1" timeout="10" action="input-method.php" method="GET">
        <?php if (!isset($_REQUEST["Digits"])) { ?>
        <Say voice="<?php echo setting('voice') ?>" language="<?php echo setting('language') ?>">
                <?php echo setting('title') ?>
        </Say>
        <?php } ?>
        <Say voice="<?php echo setting('voice') ?>" language="<?php echo setting('language') ?>">
            <?php echo word('press') ?> <?php echo word('one') ?> <?php echo word('to_find') ?> <?php echo word('someone_to_talk_to') ?>
        </Say>
        <Say voice="<?php echo setting('voice') ?>" language="<?php echo setting('language') ?>">
            <?php echo word('press') ?> <?php echo word('two') ?> <?php echo word('to_search_for') ?> <?php echo word('meetings') ?>
        </Say>
        <?php
        if (has_setting('jft_option') && setting('jft_option')) { ?>
        <Say voice="<?php echo setting('voice') ?>" language="<?php echo setting('language') ?>">
            <?php echo word('press') ?> <?php echo word('three') ?> <?php echo word('to_listen_to_the_just_for_today') ?>
        </Say>
        <?php } ?>
    </Gather>
</Response>
