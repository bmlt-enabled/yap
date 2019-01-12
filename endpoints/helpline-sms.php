<?php
require_once '_includes/functions.php';
require_once 'twilio-client.php';

try {
    if (isset( $_REQUEST["OriginalCallerId"] )) {
        $original_caller_id = $_REQUEST["OriginalCallerId"];
    }

    $service_body = getServiceBodyCoverage( $_REQUEST['Latitude'], $_REQUEST['Longitude'] );
    $serviceBodyCallHandling   = getServiceBodyCallHandling($service_body->id);
    $tracker                    = !isset( $_REQUEST["tracker"] ) ? 0 : $_REQUEST["tracker"];

    if ($serviceBodyCallHandling->sms_routing_enabled) {
        $phone_numbers = explode(',', getHelplineVolunteer( $serviceBodyCallHandling->service_body_id, $tracker, $serviceBodyCallHandling->sms_strategy, VolunteerType::SMS ));

        $twilioClient->messages->create(
            $original_caller_id,
            array(
                "body" => word('your_request_has_been_received'),
                "from" => $_REQUEST['To']
            ) );

        foreach ($phone_numbers as $phone_number) {
            if ($phone_number == SpecialPhoneNumber::UNKNOWN) {
                $phone_number = $serviceBodyCallHandling->primary_contact_number;
            }

            $twilioClient->messages->create(
                $phone_number,
                array(
                    "body" => word('helpline') . ": " . word('someone_is_requesting_sms_help_from') . " " . $original_caller_id . ", " . word('please_call_or_text_them_back'),
                    "from" => $_REQUEST['To']
                ) );
        }
    }
} catch ( Exception $e ) {
    $twilioClient->messages->create(
        $original_caller_id,
        array(
            "body" => word('could_not_find_a_volunteer'),
            "from" => $_REQUEST['To']
        ) );
}
