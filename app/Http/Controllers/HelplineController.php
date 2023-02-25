<?php

namespace App\Http\Controllers;

use App\Constants\EventId;
use App\Services\ConfigService;
use App\Services\VoicemailService;
use Exception;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class HelplineController extends Controller
{
    protected ConfigService $config;

    public function __construct(ConfigService $config)
    {
        $this->config = $config;
    }

    public function search(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';

        $twiml = new VoiceResponse();
        $dial_string = "";

        if (!$request->has('ForceNumber')) {
            if (isset($_SESSION["override_service_body_id"])) {
                $service_body_obj = getServiceBody(setting("service_body_id"));
            } else {
                $address = isset($_SESSION['Address']) ? $_SESSION['Address'] : getIvrResponse();
                if ($address == null) {
//            $twiml->say(word('you_might_have_invalid_entry'))
//                ->setVoice(voice())
//                ->setLanguage(setting('language'));
//            $twiml->redirect("index.php");
//            return response($twiml)->header("Content-Type", "text/xml");
                }
                $coordinates  = getCoordinatesForAddress($address);
                try {
                    if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) {
                        throw new Exception("Couldn't find an address for that location.");
                    }

                    $service_body_obj = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);
                } catch (Exception $e) {
                    $twiml->redirect("input-method.php?Digits=" . $request->get("SearchType") . "&Retry=1&RetryMessage=" . urlencode($e->getMessage()))
                        ->setMethod("GET");
                    return response($twiml)->header("Content-Type", "text/xml");
                }
            }

            $location    = $service_body_obj->name;
            if (isset($service_body_obj->helpline)) {
                $dial_string = explode(":", $service_body_obj->helpline)[0];
            } else {
                $dial_string = has_setting("fallback_number") ? setting("fallback_number") : "0000000000";
            }

            $waiting_message = true;
            $captcha = false;
        } else {
            $dial_string = $request->get('ForceNumber');
            $waiting_message = isset($GLOBALS['force_dialing_notice']) || $request->has('WaitingMessage');
            $captcha = $request->has('Captcha');
            $captcha_verified = $request->has('CaptchaVerified');
        }

        $exploded_result = explode("|", $dial_string);
        $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
        $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
        $service_body_id = isset($service_body_obj) ? $service_body_obj->id : 0;
        $GLOBALS['service_body_id'] = $service_body_id;

        if (!$request->has('ForceNumber')
            && (isset($_SESSION["override_service_body_id"]) || isServiceBodyHelplingCallInternallyRoutable($coordinates->latitude, $coordinates->longitude))) {
            $serviceBodyCallHandling = $this->config->getCallHandling($service_body_id);
        }

        if (isset($address)) {
            insertCallEventRecord(
                EventId::VOLUNTEER_SEARCH,
                (object)['gather' => $address, 'coordinates' => isset($coordinates) ? $coordinates : null]
            );
        } else {
            insertCallEventRecord(EventId::VOLUNTEER_SEARCH);
        }

        if ($service_body_id > 0 && isset($serviceBodyCallHandling)
            && $serviceBodyCallHandling->volunteer_routing_enabled) {
            if ($serviceBodyCallHandling->gender_routing_enabled && !isset($_SESSION['Gender'])) {
                if (isset($address)) {
                    $_SESSION["Address"] = $address;
                }

                $searchType = $request->get("SearchType") ?? "-1";
                $twiml->redirect("gender-routing.php?SearchType=" . urlencode($searchType))->setMethod("GET");
                return response($twiml)->header("Content-Type", "text/xml");
            } elseif ($serviceBodyCallHandling->volunteer_routing_redirect
                && $serviceBodyCallHandling->volunteer_routing_redirect_id > 0) {
                $calculated_service_body_id = $serviceBodyCallHandling->volunteer_routing_redirect_id;
                $serviceBodyCallHandling = $this->config->getCallHandling($calculated_service_body_id);
            } else {
                $calculated_service_body_id = $service_body_id;
            }

            if (setting("announce_servicebody_volunteer_routing")) {
                $twiml->say(sprintf("%s... %s %s", word('please_stand_by'), word('relocating_your_call_to'), $location))
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            } else {
                $twiml->say(word('please_wait_while_we_connect_your_call'))
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            }

            $dial = $twiml->dial();
            $dial->conference(getConferenceName($calculated_service_body_id))
                ->setWaitUrl($serviceBodyCallHandling->moh_count == 1 ? $serviceBodyCallHandling->moh : "playlist.php?items=" . $serviceBodyCallHandling->moh)
                ->setStatusCallback("helpline-dialer.php?service_body_id=" . $calculated_service_body_id . "&Caller=" . $request->get('Called') . getSessionLink(true))
                ->setStartConferenceOnEnter("false")
                ->setEndConferenceOnExit("true")
                ->setStatusCallbackMethod("GET")
                ->setStatusCallbackEvent("start join end leave")
                ->setWaitMethod("GET")
                ->setBeep("false");
        } elseif ($phone_number != "") {
            if (!$request->has("ForceNumber")) {
                $twiml->say(word('please_stand_by') . "... " . word('relocating_your_call_to') . "... " . $location)
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            } elseif ($request->has("ForceNumber")) {
                if ($captcha) {
                    $gather = $twiml->gather()
                        ->setLanguage(setting('gather_language'))
                        ->setHints(setting('gather_hints'))
                        ->setInput("dtmf")
                        ->setTimeout(15)
                        ->setNumDigits(1)
                        ->setAction("helpline-search.php?CaptchaVerified=1&ForceNumber=" . urlencode($request->get('ForceNumber')) . getSessionLink(true) . " " . $waiting_message ? "&amp;WaitingMessage=1" : "");

                    $gather->say(setting('title') .  "..." . word('press_any_key_to_continue'))
                        ->setVoice(voice())
                        ->setLanguage(setting('language'));
                    $twiml->hangup();
                } elseif ($waiting_message) {
                    $twiml->say(!$captcha_verified ? setting('title') : "" .  word('please_wait_while_we_connect_your_call'))
                        ->setVoice(voice())
                        ->setLanguage(setting('language'));
                }
            }
            insertCallEventRecord(EventId::HELPLINE_ROUTE, (object)["helpline_number" => $phone_number, "extension" => $extension]);
            $dial = $twiml->dial();
            $dial->number($phone_number)
                ->setSendDigits($extension);
        } else {
            $twiml->redirect("input-method.php?Digits=" . urlencode($_REQUEST["SearchType"]) . "&Retry=1&RetryMessage=" . urlencode(word('the_location_you_entered_is_not_found')))
                ->setMethod("GET");
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }
}
