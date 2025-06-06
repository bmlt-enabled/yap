<?php

namespace App\Structures;

use App\Constants\CycleAlgorithm;
use App\Constants\SpecialPhoneNumber;

class ServiceBodyCallHandling extends Structure
{
    public $service_body_id;
    public $service_body_name;
    public $service_body_parent_id;
    public $service_body_parent_name;
    public $volunteer_routing;
    public $volunteer_routing_enabled = false;
    public $volunteer_routing_redirect = false;
    public $volunteers_redirect_id = 0;
    public $volunteer_routing_redirect_id = 0; // TODO: this probably needs to be encapsulated
    public $forced_caller_id_enabled = false;
    public $forced_caller_id_number = SpecialPhoneNumber::UNKNOWN;
    public $call_timeout = 20;
    public $volunteer_sms_notification_enabled = false;
    public $gender_routing = false;
    public $gender_routing_enabled = false;
    public $call_strategy = CycleAlgorithm::LINEAR_LOOP_FOREVER;
    public $primary_contact_number_enabled = false;
    public $primary_contact;
    public $primary_contact_number = SpecialPhoneNumber::UNKNOWN;
    public $primary_contact_email_enabled = false;
    public $primary_contact_email;
    public $moh = "https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical";
    public $moh_count = 1;
    public $sms_routing_enabled = false;
    public $sms_strategy = CycleAlgorithm::RANDOM_LOOP_FOREVER;
    public $override_en_US_greeting;
    public $override_en_US_voicemail_greeting;

    public function __construct($serviceBodyCallHandling = null)
    {
        if ($serviceBodyCallHandling) {
            // Dynamically assign all properties from the passed group object
            foreach (get_object_vars($serviceBodyCallHandling) as $property => $value) {
                $this->$property = $value;
            }
        }
    }
}
