<?php
    require_once '_includes/functions.php';
    require_once '_includes/twilio-client.php';
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    log_debug("version: " . $GLOBALS['version']);
    $digit = getDigitResponse('language_selections', 'Digits');

if (strlen(setting('language_selections')) > 0) {
    if ($digit == null) {?>
            <Response><Redirect>lng-selector.php</Redirect></Response>
        <?php
        return;
    } else {
        $selected_language = explode(",", setting('language_selections'))[intval($digit) - 1];
        $_SESSION["override_word_language"] = $selected_language;
        $_SESSION["override_gather_language"] = $selected_language;
        $_SESSION["override_language"] = $selected_language;
        include_once __DIR__.'/../lang/'.getWordLanguage().'.php';
    }
}

if (isset($_REQUEST['CallSid'])) {
    $phoneNumberSid = $twilioClient->calls($_REQUEST['CallSid'])->fetch()->phoneNumberSid;
    $incomingPhoneNumber = $twilioClient->incomingPhoneNumbers($phoneNumberSid)->fetch();

    if ($incomingPhoneNumber->statusCallback == null || !str_exists($incomingPhoneNumber->statusCallback, "status.php")) {
        insertAlert(AlertId::STATUS_CALLBACK_MISSING, $incomingPhoneNumber->phoneNumber);
    }
}

if (isset($_REQUEST["override_service_body_id"])) {
    getServiceBodyCallHandling($_REQUEST["override_service_body_id"]);
}

$promptset_name = str_replace("-", "_", getWordLanguage()) . "_greeting";
if (has_setting("extension_dial") && json_decode(setting("extension_dial"))) {?>
    <Response>
        <Gather language="<?php echo setting('gather_language') ?>" input="dtmf" finishOnKey="#" timeout="10" action="service-body-ext-response.php" method="GET">
            <Say>Enter the service body ID, followed by the pound sign.</Say>
        </Gather>
    </Response>
<?php } else { ?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" input="<?php echo getInputType() ?>" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">
        <Pause length="<?php echo setting('initial_pause') ?>"></Pause>
        <?php if (has_setting($promptset_name)) {?>
            <Play><?php echo setting($promptset_name) ?></Play>
        <?php } else { ?>
            <?php if (!isset($_REQUEST["Digits"])) { ?>
                <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>"><?php echo setting('title') ?></Say>
            <?php }

            $searchTypeSequence = getDigitMapSequence('digit_map_search_type');
            foreach ($searchTypeSequence as $digit => $type) {
                if ($type == SearchType::VOLUNTEERS) { ?>
                    <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>"><?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_find') . " " . word('someone_to_talk_to') ?></Say>
                <?php }

                if ($type == SearchType::MEETINGS) { ?>
                    <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>"><?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_search_for') . " " . word('meetings') ?></Say>
                <?php }

                if ($type == SearchType::JFT) {?>
                    <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                        <?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_listen_to_the_just_for_today') ?>
                    </Say>
                <?php }

                if ($type == SearchType::SPAD) {?>
                    <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                        <?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_listen_to_the_spad') ?>
                    </Say>
                <?php }
            }
        }?>
    </Gather>
</Response>
<?php }
