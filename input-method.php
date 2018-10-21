<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    include 'functions.php';
    $searchType = getIvrResponse("index.php", "", ["1", "2", "3"]);
    $playTitle = isset($_REQUEST['PlayTitle']) ? $_REQUEST['PlayTitle'] : 0;
    
    if ($searchType == "1") {
        if (isset($_SESSION['override_service_body_id'])) { ?>
            <Response>
            <Redirect method="GET">helpline-search.php?Called=<?php echo $_REQUEST["Called"]; ?></Redirect>
            </Response>
            <?php
            exit();
        }

        $searchDescription = word('someone_to_talk_to');
    } else if ($searchType == "2") {
        if (!strpos(setting('custom_query'), '{LATITUDE}') || !strpos(setting('custom_query'), '{LONGITUDE}')) { ?>
            <Response>
                <Redirect method="GET">meeting-search.php?Called=<?php echo $_REQUEST["Called"]; ?></Redirect>
            </Response>
            <?php
            exit();
        }

        $searchDescription = word('meetings');
    } else if ($searchType == "3" ) { ?>
        <Response>
        <Redirect method="GET">fetch-jft.php</Redirect>
        </Response>
        <?php
        exit();
    } else {

    }
?>
<Response>
    <Gather input="speech dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method-result.php?SearchType=<?php echo $searchType ?>" method="GET">
    	<?php
        if ($playTitle == "1") { ?>
            <Say voice="<?php echo setting("voice") ?>" language="<?php echo setting("language")?>"><?php echo setting("title")?></Say>
		<?php }
        if (isset($_REQUEST["Retry"])) {
            $retry_message = isset($_REQUEST["RetryMessage"]) ? $_REQUEST["RetryMessage"] : word("could_not_find_location_please_retry_your_entry");?>
            <Say voice="<?php echo setting("voice") ?>" language="<?php echo setting("language")?>"><?php echo $retry_message?></Say>
            <Pause length="1"/>
        <?php } ?>
       
        <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('press') . " " . word('one') . " " . word('to_search_for') . " " . $searchDescription . " " . word ('by') . " " . word('city_or_county') ?>
        </Say>
        <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('press') . " "  . word('two') . " " . word('to_search_for') . " " . $searchDescription . " " . word ('by') . " " . word('zip_code') ?>
        </Say>

        <?php
            if ($searchType == "2") {
                if (has_setting('jft_option') && json_decode(setting('jft_option'))) { ?>
                    <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
                        <?php echo word('press') . " " . word('three') . " " . word('to_listen_to_the_just_for_today') ?>
                </Say>
                <?php }
            }
        ?>

    </Gather>
</Response>
