<?php
    require_once '_includes/functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    getIvrResponse('index.php', null, getDigitMapSequence('digit_map_search_type'), array(), 'Digits');
    $searchType = getDigitResponse('digit_map_search_type', 'Digits');
    $playTitle = isset($_REQUEST['PlayTitle']) ? $_REQUEST['PlayTitle'] : 0;


if ($searchType == SearchType::MEETINGS || $searchType == SearchType::JFT) {
    writeMetric(["searchType" => $searchType], setting('service_body_id'));
}

if ($searchType == SearchType::VOLUNTEERS) {
    if (isset($_SESSION['override_service_body_id'])) { ?>
            <Response>
                <Redirect method="GET">helpline-search.php?Called=<?php echo $_REQUEST["Called"] . getSessionLink(true)?></Redirect>
            </Response>
            <?php
            exit();
    }

    $searchDescription = word('someone_to_talk_to');
} else if ($searchType == SearchType::MEETINGS) {
    if (!strpos(setting('custom_query'), '{LATITUDE}') || !strpos(setting('custom_query'), '{LONGITUDE}')) { ?>
            <Response>
                <Redirect method="GET">meeting-search.php?Called=<?php echo $_REQUEST["Called"]; ?></Redirect>
            </Response>
            <?php
            exit();
    }

    $searchDescription = word('meetings');
} else if ($searchType == SearchType::JFT) { ?>
        <Response>
        <Redirect method="GET">fetch-jft.php</Redirect>
        </Response>
        <?php
        exit();
} else {
}
?>
<Response>
    <Gather language="<?php echo setting('gather_language') ?>" input="<?php echo getInputType() ?>" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=<?php echo $_REQUEST['Digits'] ?>" method="GET">
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
                <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                    <?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_search_for') . " " . $searchDescription . " " . word('by') . " " . word('city_or_county') ?>
                </Say>
            <?php }

            if ($method == LocationSearchMethod::DTMF) { ?>
                 <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                    <?php echo word('press') . " " . getWordForNumber($digit) . " " . word('to_search_for') . " " . $searchDescription . " " . word('by') . " " . word('zip_code') ?>
                </Say>
            <?php }

            if ($method == SearchType::JFT && $searchType == SearchType::MEETINGS) { ?>
                <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                    <?php echo getPressWord() . " " . getWordForNumber($digit) . " " . word('to_listen_to_the_just_for_today') ?>
                </Say>
            <?php }
        } ?>
    </Gather>
</Response>
