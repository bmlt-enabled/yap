<?php
    require_once '_includes/functions.php';
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $response = getIvrResponse('index.php', null, getPossibleDigits('digit_map_search_type'), array(), 'Digits');
if ($response == null) {
    return;
}
    $searchType = getDigitResponse('digit_map_search_type', 'Digits');
    $playTitle = isset($_REQUEST['PlayTitle']) ? $_REQUEST['PlayTitle'] : 0;


if ($searchType == SearchType::MEETINGS) {
    insertCallEventRecord(EventId::MEETING_SEARCH);
} elseif ($searchType == SearchType::JFT) {
    insertCallEventRecord(EventId::JFT_LOOKUP);
}?>

<Response>
<?php
if ($searchType == SearchType::MEETINGS && !json_decode(setting("sms_ask")) && !json_decode(setting("sms_disable"))) { ?>
    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
        <?php echo word('search_results_by_sms') ?>
    </Say>
<?php }

if (($searchType == SearchType::VOLUNTEERS || $searchType == SearchType::MEETINGS)
    && json_decode(setting('disable_postal_code_gather'))) { ?>
        <Redirect method="GET">input-method-result.php?SearchType=<?php echo $searchType ?>&amp;Digits=1</Redirect>
    </Response>
    <?php
    return;
} elseif ($searchType == SearchType::VOLUNTEERS) {
    if (isset($_SESSION['override_service_body_id'])) { ?>
                <Redirect method="GET">helpline-search.php?Called=<?php echo $_REQUEST["Called"] . getSessionLink(true)?></Redirect>
            </Response>
            <?php
            return;
    }

    $searchDescription = word('someone_to_talk_to');
} elseif ($searchType == SearchType::MEETINGS) {
    if (!strpos(setting('custom_query'), '{LATITUDE}') || !strpos(setting('custom_query'), '{LONGITUDE}')) { ?>
                <Redirect method="GET">meeting-search.php?Called=<?php echo $_REQUEST["Called"]; ?></Redirect>
            </Response>
            <?php
            return;
    }

    $searchDescription = word('meetings');
} elseif ($searchType == SearchType::JFT) { ?>
        <Redirect method="GET">fetch-jft.php</Redirect>
        </Response>
        <?php
        return;
} elseif ($searchType == SearchType::DIALBACK) { ?>
        <Redirect method="GET">dialback.php</Redirect>
        </Response>
        <?php
        return;
} elseif ($searchType == SearchType::CUSTOM_EXTENSIONS && count(setting('custom_extensions')) > 0) { ?>
        <Redirect method="GET">custom-ext.php</Redirect>
        </Response>
        <?php
        return;
} ?>
    <Gather language="<?php echo setting('gather_language') ?>" input="<?php echo getInputType() ?>" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=<?php echo $searchType?>" method="GET">
        <?php
        if ($playTitle == "1") { ?>
            <Say voice="<?php echo voice() ?>" language="<?php echo setting("language")?>"><?php echo setting("title")?></Say>
        <?php }
        if (isset($_REQUEST["Retry"])) {
            $retry_message = isset($_REQUEST["RetryMessage"]) ? $_REQUEST["RetryMessage"] : word("could_not_find_location_please_retry_your_entry");?>
            <Say voice="<?php echo voice() ?>" language="<?php echo setting("language")?>"><?php echo $retry_message?></Say>
            <Pause length="1"/>
        <?php }

        $locationSearchMethodSequence = getDigitMapSequence('digit_map_location_search_method');
        foreach ($locationSearchMethodSequence as $digit => $method) {
            if ($method == LocationSearchMethod::VOICE) { ?>
                <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>"><?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_search_for') . " " . $searchDescription . " " . word('by') . " " . word('city_or_county') ?></Say>
            <?php }

            if ($method == LocationSearchMethod::DTMF) { ?>
                 <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>"><?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_search_for') . " " . $searchDescription . " " . word('by') . " " . word('zip_code') ?></Say>
            <?php }

            if ($method == SearchType::JFT && $searchType == SearchType::MEETINGS) { ?>
                <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                    <?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_listen_to_the_just_for_today') ?>
                </Say>
            <?php }
        } ?>
    </Gather>
</Response>
