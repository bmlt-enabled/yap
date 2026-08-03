<?php

namespace App\Http\Controllers;

use App\Services\ChatSessionService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\MessagingResponse;

class WebChatSmsController extends Controller
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Handle incoming SMS from volunteers responding to web chat
     * This endpoint receives Twilio SMS webhook callbacks
     */
    public function handleSms(Request $request)
    {
        // WebChat is experimental and disabled by default. When it is off this
        // endpoint must not route anything: receiveVolunteerReply() matches an
        // inbound SMS to a chat session by phone number, so an ungated call is a
        // confidentiality risk, not just dead code.
        if (!$this->isWebchatEnabled()) {
            Log::debug("WebChat SMS received while webchat is disabled, ignoring");
            return $this->emptyResponse();
        }

        $from = $request->get('From');
        $to = $request->get('To');
        $body = $request->get('Body');

        Log::debug("WebChat SMS received", [
            'from' => $from,
            'to' => $to,
            'body_length' => strlen($body ?? ''),
        ]);

        if (empty($from) || empty($body)) {
            return $this->emptyResponse();
        }

        // Resolved lazily so a disabled webchat never builds the chat session
        // service graph (Twilio client, root server, meeting results).
        $chatService = App::make(ChatSessionService::class);

        // Try to route this to a chat session
        $result = $chatService->receiveVolunteerReply($from, $body, $to);

        if (!$result['success']) {
            // No active chat session - this might be a regular SMS, pass through
            Log::debug("WebChat SMS: No active session for {$from}, ignoring");
            return $this->emptyResponse();
        }

        Log::info("WebChat SMS: Message from {$from} delivered to session {$result['session_id']}");

        // Return empty TwiML response - we don't send an automatic reply
        return $this->emptyResponse();
    }

    /**
     * Check if webchat is enabled
     *
     * Deliberately does not use SettingsService::has(): has() is
     * !is_null(get()), and get() falls back to the allowlist default of false,
     * so has() is always true for this setting and would mean nothing here.
     */
    protected function isWebchatEnabled(): bool
    {
        return (bool)$this->settings->get('webchat_enabled');
    }

    /**
     * Return an empty TwiML response
     */
    protected function emptyResponse()
    {
        $twiml = new MessagingResponse();
        return response($twiml)->header('Content-Type', 'text/xml');
    }
}
