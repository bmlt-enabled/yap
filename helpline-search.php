<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $dial_string = "";
    if (!isset($_REQUEST['ForceNumber'])) {
        $address = $_REQUEST['Digits'];

        $coordinates  = getCoordinatesForAddress( $address );
        $service_body = getServiceBodyCoverage( $coordinates->latitude, $coordinates->longitude );

        $location    = $service_body->name;
        $dial_string = $service_body->helpline;
    } else {
        $dial_string = $_REQUEST['ForceNumber'];
    }

    $exploded_result = explode("|", $dial_string);
    $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
    $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
?>
<Response>
    <?php if (strpos($phone_number, 'yap') !== false) {
        $yap_service_body_redirect = strpos($phone_number, '->') !== false
            ? explode('->', $phone_number)[1]
            : $service_body->id;
        ?>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
            <?php echo word('please_wait_while_we_connect_your_call') ?>
        </Say>
        <Redirect method="GET">helpline-dialer.php?service_body_id=<?php echo $yap_service_body_redirect ?></Redirect>
    <?php } else if ($phone_number != "") {
        if (!isset($_REQUEST["ForceNumber"])) { ?>
            <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>"><?php echo word('please_stand_by') ?>... <?php echo word('relocating_your_call_to') ?> <?php echo $location; ?>.</Say>
        <?php } elseif (isset($_REQUEST["ForceNumber"]) && isset($GLOBALS['force_dialing_notice'])) {
            if ( isset( $_REQUEST["Captcha"] ) ) { ?>
                <Gather language="<?php echo getGatherLanguage(); ?>"
                        hints="<?php echo getGatherHints();?>"
                        input="dtmf"
                        timeout="15"
                        numDigits="1"
                        action="helpline-search.php?ForceNumber=<?php echo urlencode($_REQUEST['ForceNumber'])?>">
                    <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
                        <?php echo word( 'press_any_key_to_continue' ) ?>
                    </Say>
                </Gather>
                <Hangup/>
            <?php } else { ?>
                <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">
                    <?php echo word( 'please_wait_while_we_connect_your_call' ) ?>
                </Say>
            <?php }
        }?>
        <Dial>
            <Number sendDigits="<?php echo $extension ?>"><?php echo $phone_number ?></Number>
        </Dial>
    <?php } else { ?>
        <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>"><?php echo word('the_location_you_entered_is_not_found') ?></Say>
        <Redirect method="GET">zip-input.php?Digits=1</Redirect>
    <?php } ?>
</Response>