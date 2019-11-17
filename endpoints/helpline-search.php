<?php
    require_once '_includes/functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $dial_string = "";?>
<Response>
<?php
if (!isset($_REQUEST['ForceNumber'])) {
    if (isset($_SESSION["override_service_body_id"])) {
        $service_body_obj = getServiceBody(setting("service_body_id"));
    } else {
        $address = isset($_SESSION['Address']) ? $_SESSION['Address'] : getIvrResponse();
        $coordinates  = getCoordinatesForAddress($address);
        try {
            if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) {
                throw new Exception("Couldn't find an address for that location.");
            }

            if (isset($GLOBALS['pusher_auth_key']) && isset($GLOBALS['pusher_secret']) && isset($GLOBALS['pusher_app_id'])) {
                require __DIR__ . '/../vendor/autoload.php';
                $pusher = new Pusher\Pusher(
                    $GLOBALS['pusher_auth_key'],
                    $GLOBALS['pusher_secret'],
                    $GLOBALS['pusher_app_id'],
                    array(
                        'cluster' => 'us2',
                        'useTLS' => true
                    )
                );
                $pusher->trigger('yap-viz', 'helpline-search', $coordinates);
            }

            $service_body_obj = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);
        } catch (Exception $e) { ?>
                <Redirect method="GET">input-method.php?Digits=<?php echo urlencode($_REQUEST["SearchType"]) . "&amp;Retry=1&amp;RetryMessage=" . urlencode($e->getMessage()); ?></Redirect>
                </Response>
                <?php
                exit();
        }
    }
    $location    = $service_body_obj->name;
    $dial_string = explode(":", $service_body_obj->helpline)[0];
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
    $service_body_id = isset($service_body_obj) ? $service_body_obj->id : 0;

    $serviceBodyCallHandling = getServiceBodyCallHandling($service_body_id);

insertCallEventRecord(EventId::VOLUNTEER_SEARCH,
    (object)['gather' => $address, 'coordinates' => isset($coordinates) ? $coordinates : null]);

if ($service_body_id > 0 && isset($serviceBodyCallHandling) && $serviceBodyCallHandling->volunteer_routing_enabled) {
    if ($serviceBodyCallHandling->gender_routing_enabled && !isset($_SESSION['Gender'])) {
        $_SESSION['Address'] = $address; ?>
                <Redirect method="GET">gender-routing.php?SearchType=<?php echo urlencode($_REQUEST["SearchType"])?></Redirect>
            </Response>
            <?php
            exit();
    } else if ($serviceBodyCallHandling->volunteer_routing_redirect && $serviceBodyCallHandling->volunteer_routing_redirect_id > 0) {
        $calculated_service_body_id = $serviceBodyCallHandling->volunteer_routing_redirect_id;
    } else {
        $calculated_service_body_id = $service_body_id;
    }
    ?>
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>"><?php echo word('please_wait_while_we_connect_your_call') ?></Say>
        <Dial>
            <Conference waitUrl="<?php echo $serviceBodyCallHandling->moh_count == 1 ? $serviceBodyCallHandling->moh : "playlist.php?items=" . $serviceBodyCallHandling->moh?>"
                        statusCallback="helpline-dialer.php?service_body_id=<?php echo $calculated_service_body_id ?>&amp;Caller=<?php echo $_REQUEST['Called'] . getSessionLink(true) ?>"
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
            <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>"><?php echo word('please_stand_by') ?>... <?php echo word('relocating_your_call_to') ?> <?php echo $location; ?>.</Say>
    <?php } elseif (isset($_REQUEST["ForceNumber"])) {
        if ($captcha) { ?>
                <Gather language="<?php echo setting('gather_language') ?>"
                        hints="<?php echo setting('gather_hints')?>"
                        input="dtmf"
                        timeout="15"
                        numDigits="1"
                        action="helpline-search.php?CaptchaVerified=1&amp;ForceNumber=<?php echo urlencode($_REQUEST['ForceNumber']) . getSessionLink(true) ?><?php echo $waiting_message ? "&amp;WaitingMessage=1" : "" ?>">
                    <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                        <?php echo setting('title') ?>... <?php echo word('press_any_key_to_continue') ?>
                    </Say>
                </Gather>
                <Hangup/>
        <?php } else if ($waiting_message) { ?>
                <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
                    <?php echo !$captcha_verified ? setting('title') : "" ?> <?php echo word('please_wait_while_we_connect_your_call') ?>
                </Say>
        <?php }
    }?>
        <Dial>
            <Number sendDigits="<?php echo $extension ?>"><?php echo $phone_number ?></Number>
        </Dial>
<?php } else { ?>
        <Redirect method="GET">input-method.php?Digits=<?php echo urlencode($_REQUEST["SearchType"]) . "&amp;Retry=1&amp;RetryMessage=" . urlencode(word('the_location_you_entered_is_not_found'));?></Redirect>
<?php } ?>
</Response>
