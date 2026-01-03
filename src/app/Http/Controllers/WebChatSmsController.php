<?php

namespace App\Http\Controllers;

use App\Services\ChatSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\MessagingResponse;

class WebChatSmsController extends Controller
{
    protected ChatSessionService $chatService;

    public function __construct(ChatSessionService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Handle incoming SMS from volunteers responding to web chat
     * This endpoint receives Twilio SMS webhook callbacks
     */
    public function handleSms(Request $request)
    {
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

        // Try to route this to a chat session
        $result = $this->chatService->receiveVolunteerReply($from, $body, $to);

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
     * Return an empty TwiML response
     */
    protected function emptyResponse()
    {
        $twiml = new MessagingResponse();
        return response($twiml)->header('Content-Type', 'text/xml');
    }
}
