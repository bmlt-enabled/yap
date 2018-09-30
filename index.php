<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    if (strlen(setting('language_selections')) > 0) {
        if (!isset($_REQUEST["Digits"])) {?>
            <Response><Redirect>language-selector.php</Redirect></Response>
        <?php
            exit();
        } else {
            $selected_language = explode(",", setting('language_selections'))[intval($_REQUEST["Digits"]) - 1];
            $_SESSION["override_word_language"] = $selected_language;
            $_SESSION["override_gather_language"] = $selected_language;
            include_once 'lang/'.getWordLanguage().'.php';
        }
    }

    if (isset($_REQUEST["override_service_body_id"])) {
        getServiceBodyConfiguration($_REQUEST["override_service_body_id"]);
    }

    $promptset_name = str_replace("-", "_", getWordLanguage()) . "_greeting";
?>
<Response>
    <Gather input="speech dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">
        <Pause length="2"></Pause>
        <?php if (has_setting($promptset_name)) {?>
            <Play><?php echo setting($promptset_name) ?></Play>
        <?php } else { ?>
            <?php if ( ! isset( $_REQUEST["Digits"] ) ) { ?>
                <Say voice="<?php echo setting( 'voice' ) ?>" language="<?php echo setting( 'language' ) ?>">
                    <?php echo setting( 'title' ) ?>
                </Say>
            <?php } ?>
            <Say voice="<?php echo setting( 'voice' ) ?>" language="<?php echo setting( 'language' ) ?>">
                <?php echo word( 'press' ) . " " . word( 'one' ) . " " . word( 'to_find' ) . " " . word( 'someone_to_talk_to' ) ?>
            </Say>
            <Say voice="<?php echo setting( 'voice' ) ?>" language="<?php echo setting( 'language' ) ?>">
                <?php echo word( 'press' ) . " " . word( 'two' ) . " " . word( 'to_search_for' ) . " " . word( 'meetings' ) ?>
            </Say>
            <?php
            if ( has_setting( 'jft_option' ) && json_decode(setting( 'jft_option' )) ) { ?>
                <Say voice="<?php echo setting( 'voice' ) ?>" language="<?php echo setting( 'language' ) ?>">
                    <?php echo word( 'press' ) . " " . word( 'three' ) . " " . word( 'to_listen_to_the_just_for_today' ) ?>
                </Say>
            <?php }
        }?>
    </Gather>
</Response>
