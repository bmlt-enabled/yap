<?php

namespace App\Http\Controllers;

use App\Constants\EventId;
use App\Services\ConfigService;
use App\Services\VoicemailService;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use VolunteerResponderOption;

class VoicemailController extends Controller
{
    protected ConfigService $config;
    protected VoicemailService $voicemailService;

    public function __construct(ConfigService $config, VoicemailService $voicemailService)
    {
        $this->config = $config;
        $this->voicemailService = $voicemailService;
    }

    public function start(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        getServiceBodyCallHandling(setting("service_body_id"));
        $promptset_name = str_replace("-", "_", getWordLanguage()) . "_voicemail_greeting";

        $twiml = new VoiceResponse();
        if (has_setting($promptset_name)) {
            $twiml->play(setting($promptset_name));
        } else {
            $say = word("please_leave_a_message_after_the_tone").", ".word("hang_up_when_finished");
            $twiml->say($say)
                ->setVoice(voice())
                ->setLanguage(setting("language"));
        }

        $recordingStatusCallback = "voicemail-complete.php?service_body_id=".setting("service_body_id").
            "&caller_id=".urlencode($request->query("caller_id"))."&caller_number=".
            urlencode($request->query("Caller")).getSessionLink(true);

        $twiml->record()
            ->setPlayBeep(true)
            ->setMaxLength(120)
            ->setTimeout(15)
            ->setRecordingStatusCallback($recordingStatusCallback)
            ->setRecordingStatusCallbackMethod("GET");

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function complete(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';
        insertCallEventRecord(
            EventId::VOICEMAIL,
            (object)['url' => $request->has('RecordingUrl') ? $request->get('RecordingUrl') : null]
        );
        $callSid = $request->get('CallSid');
        $recordingUrl = $request->get('RecordingUrl');
        hup($callSid);

        $serviceBodyCallHandling = $this->config->getCallHandling(setting("service_body_id"));
        $serviceBodyName = getServiceBody(setting("service_body_id"))->name;
        $callerNumber = $request->get("caller_number");
        if (strpos(trim($callerNumber), "+") !== 0) {
            $callerNumber = "+" . trim($callerNumber);
        }

        if ($serviceBodyCallHandling->primary_contact_number_enabled) {
            $recipients = explode(",", $serviceBodyCallHandling->primary_contact_number);
            $this->voicemailService->sendSmsForVoicemail(
                $callSid,
                $recordingUrl,
                $recipients,
                $serviceBodyCallHandling,
                $serviceBodyName,
                $callerNumber
            );
        }

        if (isset($_SESSION["volunteer_routing_parameters"])) {
            $volunteer_routing_options = $_SESSION["volunteer_routing_parameters"];
            $volunteer_routing_options->volunteer_responder = VolunteerResponderOption::ENABLED;
            $volunteers = getHelplineVolunteersActiveNow($volunteer_routing_options);
            $recipients = [];
            foreach ($volunteers as $volunteer) {
                array_push($recipients, $volunteer->contact);
            }
            if (count($volunteers) > 0) {
                $this->voicemailService->sendSmsForVoicemail(
                    $callSid,
                    $recordingUrl,
                    $recipients,
                    $serviceBodyCallHandling,
                    $serviceBodyName,
                    $callerNumber
                );
            }
        }

        if ($serviceBodyCallHandling->primary_contact_email_enabled && has_setting('smtp_host')) {
            $recipients = explode(",", $serviceBodyCallHandling->primary_contact_email);
            $this->voicemailService->sendEmailForVoicemail($recordingUrl, $recipients, $serviceBodyName, $callerNumber);
        }

        $twiml = new VoiceResponse();
        return response($twiml)->header("Content-Type", "text/xml");
    }
}
