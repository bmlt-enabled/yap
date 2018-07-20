<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $dial_string = "";
    if (!isset($_REQUEST['ForceNumber'])) {
        if (isset($_SESSION["override_service_body_id"])) {
            $service_body = getServiceBody(setting("service_body_id"));
        } else {
            $address = $_REQUEST['Digits'];
            $coordinates  = getCoordinatesForAddress( $address );
            try {
                if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) {
                    throw new Exception("Couldn't find an address for that location.");
                }
                $service_body = getServiceBodyCoverage( $coordinates->latitude, $coordinates->longitude );
            } catch (Exception $e) {
                header("Location: input-method.php?Digits=" . $_REQUEST["SearchType"] . "&Retry=1&RetryMessage=" . $e->getMessage());
                exit();
            }
        }
        $location    = $service_body->name;
        $dial_string = $service_body->helpline;
        $waiting_message = true;
        $captcha = false;
    } else {
        $dial_string = $_REQUEST['ForceNumber'];
        $waiting_message = isset($GLOBALS['force_dialing_notice']) || isset($_REQUEST['WaitingMessage']);
        $captcha = isset($_REQUEST['Captcha']);
        $captcha_verified = isset($_REQUEST['CaptchaVerified']);
    }

    $exploded_result = explode("|", $dial_string);
    $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
    $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
    $service_body_id = isset($service_body) ? $service_body->id : 0;
?>
<Response>
    <?php
        $serviceBodyConfiguration = getServiceBodyConfiguration($service_body_id);
        if ($service_body_id > 0 && $serviceBodyConfiguration->volunteer_routing_enabled) {
            if ($serviceBodyConfiguration->volunteer_routing_redirect && $serviceBodyConfiguration->volunteer_routing_redirect_id > 0) {
                $calculated_service_body_id = $serviceBodyConfiguration->volunteer_routing_redirect_id;
            } else {
                $calculated_service_body_id = $service_body_id;
            }
            ?>
        <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>"><?php echo word('please_wait_while_we_connect_your_call') ?></Say>
        <Dial>
            <Conference waitUrl="<?php echo $serviceBodyConfiguration->moh_count == 1 ? $serviceBodyConfiguration->moh : "playlist.php?items=" . $serviceBodyConfiguration->moh?>"
                        statusCallback="helpline-dialer.php?service_body_id=<?php echo $calculated_service_body_id ?>&amp;Caller=<?php echo $_REQUEST['Called'] ?>"
                        startConferenceOnEnter="false"
                        endConferenceOnExit="true"
                        statusCallbackMethod="GET"
                        statusCallbackEvent="start join end leave"
                        beep="false">
                <?php echo getConferenceName($calculated_service_body_id); ?>
            </Conference>
        </Dial>
    <?php } else if ($phone_number != "") {
        if (!isset($_REQUEST["ForceNumber"])) { ?>
            <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>"><?php echo word('please_stand_by') ?>... <?php echo word('relocating_your_call_to') ?> <?php echo $location; ?>.</Say>
        <?php } elseif (isset($_REQUEST["ForceNumber"])) {
            if ($captcha) { ?>
                <Gather language="<?php echo setting('gather_language') ?>"
                        hints="<?php echo setting('gather_hints')?>"
                        input="dtmf"
                        timeout="15"
                        numDigits="1"
                        action="helpline-search.php?CaptchaVerified=1&amp;ForceNumber=<?php echo urlencode($_REQUEST['ForceNumber'])?><?php echo $waiting_message ? "&amp;WaitingMessage=1" : "" ?>">
                    <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
                        <?php echo setting('title') ?>... <?php echo word( 'press_any_key_to_continue' ) ?>
                    </Say>
                </Gather>
                <Hangup/>
        <?php } else if ($waiting_message) { ?>
                <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>">
                    <?php echo !$captcha_verified ? setting('title') : "" ?> <?php echo word( 'please_wait_while_we_connect_your_call' ) ?>
                </Say>
            <?php }
        }?>
        <Dial>
            <Number sendDigits="<?php echo $extension ?>"><?php echo $phone_number ?></Number>
        </Dial>
    <?php } else { ?>
        <Say voice="<?php echo setting('voice'); ?>" language="<?php echo setting('language') ?>"><?php echo word('the_location_you_entered_is_not_found') ?></Say>
        <Redirect method="GET">zip-input.php?Digits=1</Redirect>
    <?php } ?>
</Response>
