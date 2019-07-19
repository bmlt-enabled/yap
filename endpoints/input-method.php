<?php
    require_once '_includes/functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    getIvrResponse('index.php', null, array_keys(setting('digit_map_search_type')), array(), 'Digits');
    $searchType = getSearchType('Digits');
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
} else if ($searchType == SearchType::JFT && has_setting('jft_option') && json_decode(setting('jft_option'))) { ?>
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
        <?php } ?>

        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            <?php echo getPressWord() . " " . word('one') . " " . word('to_search_for') . " " . $searchDescription . " " . word('by') . " " . word('city_or_county') ?>
        </Say>
        <?php if (!has_setting("disable_postal_code_gather") || !setting("disable_postal_code_gather")) {?>
            <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                <?php echo word('press') . " "  . word('two') . " " . word('to_search_for') . " " . $searchDescription . " " . word('by') . " " . word('zip_code') ?>
            </Say>
        <?php }?>

        <?php
        if ($searchType == SearchType::MEETINGS) {
            if (has_setting('jft_option') && json_decode(setting('jft_option'))) { ?>
                    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                        <?php echo getPressWord() . " " . word('three') . " " . word('to_listen_to_the_just_for_today') ?>
                </Say>
            <?php }
        }
        ?>
    </Gather>
</Response>
