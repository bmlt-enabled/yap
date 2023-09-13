<?php

namespace App\Http\Controllers;

use App\Constants\EventId;
use App\Constants\VolunteerResponderOption;
use App\Services\CallService;
use App\Services\ConfigService;
use App\Services\RootServerService;
use App\Services\SettingsService;
use App\Services\TwilioService;
use App\Services\VoicemailService;
use App\Services\VolunteerService;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class VoicemailController extends Controller
{
    protected ConfigService $config;
    protected VoicemailService $voicemail;
    protected VolunteerService $volunteers;
    protected SettingsService $settings;
    protected RootServerService $rootServer;
    protected TwilioService $twilio;
    protected CallService $call;

    public function __construct(
        ConfigService $config,
        VoicemailService $voicemail,
        VolunteerService $volunteers,
        SettingsService $settings,
        RootServerService $rootServer,
        TwilioService $twilio,
        CallService $call
    ) {
        $this->config = $config;
        $this->voicemail = $voicemail;
        $this->volunteers = $volunteers;
        $this->settings = $settings;
        $this->rootServer = $rootServer;
        $this->twilio = $twilio;
        $this->call = $call;
    }

    public function start(Request $request)
    {
        $this->config->getCallHandling($this->settings->get("service_body_id"));
        $promptset_name = str_replace("-", "_", $this->settings->getWordLanguage()) . "_voicemail_greeting";

        $twiml = new VoiceResponse();
        if ($this->settings->has($promptset_name)) {
            $twiml->play($this->settings->get($promptset_name));
        } else {
            $say = $this->settings->word("please_leave_a_message_after_the_tone").
                ", ".$this->settings->word("hang_up_when_finished");
            $twiml->say($say)
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
        }

        $recordingStatusCallback = "voicemail-complete.php?service_body_id=".$this->settings->get("service_body_id").
            "&caller_id=".urlencode($request->get("caller_id"))."&caller_number=".
            urlencode($request->get("Caller")).$this->settings->getSessionLink(true);

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
        $this->call->insertCallEventRecord(
            EventId::VOICEMAIL,
            (object)['url' => $request->has('RecordingUrl') ? $request->get('RecordingUrl') : null]
        );
        $callSid = $request->get('CallSid');
        $recordingUrl = $request->get('RecordingUrl');
        $this->twilio->hup($callSid);

        $serviceBodyCallHandling = $this->config->getCallHandling($this->settings->get("service_body_id"));
        $serviceBodyName = $this->rootServer->getServiceBody($this->settings->get("service_body_id"))->name;
        $callerNumber = $request->get("caller_number");
        if (!str_starts_with(trim($callerNumber), "+")) {
            $callerNumber = "+" . trim($callerNumber);
        }

        if ($serviceBodyCallHandling->primary_contact_number_enabled) {
            $recipients = explode(",", $serviceBodyCallHandling->primary_contact_number);
            $this->voicemail->sendSmsForVoicemail(
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
            $volunteers = $this->volunteers->getHelplineVolunteersActiveNow($volunteer_routing_options);
            $recipients = [];
            foreach ($volunteers as $volunteer) {
                $recipients[] = $volunteer->contact;
            }
            if (count($volunteers) > 0) {
                $this->voicemail->sendSmsForVoicemail(
                    $callSid,
                    $recordingUrl,
                    $recipients,
                    $serviceBodyCallHandling,
                    $serviceBodyName,
                    $callerNumber
                );
            }
        }

        if ($serviceBodyCallHandling->primary_contact_email_enabled && $this->settings->has('smtp_host')) {
            $recipients = explode(",", $serviceBodyCallHandling->primary_contact_email);
            $this->voicemail->sendEmailForVoicemail($recordingUrl, $recipients, $serviceBodyName, $callerNumber);
        }

        $twiml = new VoiceResponse();
        return response($twiml)->header("Content-Type", "text/xml");
    }
}
