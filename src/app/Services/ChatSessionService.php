<?php

namespace App\Services;

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;
use App\Exceptions\NoVolunteersException;
use App\Models\ChatSession;
use App\Structures\VolunteerRoutingParameters;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class ChatSessionService extends Service
{
    protected VolunteerService $volunteerService;
    protected TwilioService $twilioService;
    protected RootServerService $rootServerService;
    protected MeetingResultsService $meetingResultsService;
    protected ConfigService $configService;

    public function __construct(
        VolunteerService $volunteerService,
        TwilioService $twilioService,
        RootServerService $rootServerService,
        MeetingResultsService $meetingResultsService,
        ConfigService $configService
    ) {
        parent::__construct(App::make(SettingsService::class));
        $this->volunteerService = $volunteerService;
        $this->twilioService = $twilioService;
        $this->rootServerService = $rootServerService;
        $this->meetingResultsService = $meetingResultsService;
        $this->configService = $configService;
    }

    /**
     * Create a new chat session and blast to volunteers
     */
    public function createSession(
        string $clientId,
        float $latitude,
        float $longitude,
        string $location,
        string $initialMessage
    ): array {
        // Find service body for this location
        $serviceBody = $this->meetingResultsService->getServiceBodyCoverage($latitude, $longitude);

        if (!$serviceBody) {
            return [
                'success' => false,
                'error' => 'no_coverage',
                'message' => $this->settings->get('webchat_no_coverage_message')
                    ?? 'Sorry, there is no coverage for your location.',
            ];
        }

        // Check if volunteer routing is enabled for this service body
        $callHandling = $this->configService->getCallHandling($serviceBody->id);
        if (!$callHandling || !$callHandling->volunteer_routing_enabled) {
            return [
                'success' => false,
                'error' => 'routing_disabled',
                'message' => $this->settings->get('webchat_no_volunteers_message')
                    ?? 'Sorry, volunteer chat is not available at this time.',
            ];
        }

        // Get active volunteers using blasting strategy
        $routingParams = new VolunteerRoutingParameters();
        $routingParams->service_body_id = $serviceBody->id;
        $routingParams->tracker = 0;
        $routingParams->cycle_algorithm = CycleAlgorithm::BLASTING;
        $routingParams->volunteer_type = VolunteerType::SMS;
        $routingParams->volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
        $routingParams->volunteer_language = $this->settings->get('language');

        try {
            $activeVolunteers = $this->volunteerService->getHelplineVolunteersActiveNow($routingParams);
        } catch (NoVolunteersException $e) {
            $activeVolunteers = [];
        }

        if (empty($activeVolunteers)) {
            return [
                'success' => false,
                'error' => 'no_volunteers',
                'message' => $this->settings->get('webchat_no_volunteers_message')
                    ?? 'Sorry, no volunteers are available at this time. Please try again later.',
            ];
        }

        // Create the session
        $session = ChatSession::create([
            'client_id' => $clientId,
            'service_body_id' => $serviceBody->id,
            'status' => ChatSession::STATUS_PENDING,
            'location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'messages' => [],
        ]);

        // Add the initial message
        $session->addMessage($initialMessage, 'user');

        // Blast SMS to all active volunteers
        $callerId = $this->getOutboundCallerId($callHandling);
        $volunteerCount = 0;

        foreach ($activeVolunteers as $volunteer) {
            if (!isset($volunteer->contact) || empty($volunteer->contact)) {
                continue;
            }

            try {
                $volunteerName = $volunteer->title ?? 'Volunteer';
                $smsBody = $this->formatVolunteerNotification($session, $initialMessage, $location);

                $this->twilioService->client()->messages->create(
                    $volunteer->contact,
                    [
                        'from' => $callerId,
                        'body' => $smsBody,
                    ]
                );

                Log::info("Chat session {$session->id}: SMS sent to volunteer {$volunteer->contact}");
                $volunteerCount++;
            } catch (\Exception $e) {
                Log::error("Chat session {$session->id}: Failed to SMS volunteer {$volunteer->contact}: " . $e->getMessage());
            }
        }

        if ($volunteerCount === 0) {
            $session->status = ChatSession::STATUS_CLOSED;
            $session->save();

            return [
                'success' => false,
                'error' => 'sms_failed',
                'message' => 'Sorry, we were unable to reach any volunteers. Please try again later.',
            ];
        }

        Log::info("Chat session {$session->id}: Created, notified {$volunteerCount} volunteers");

        return [
            'success' => true,
            'session_id' => $session->id,
            'message' => 'Your message has been sent. A volunteer will respond shortly.',
        ];
    }

    /**
     * Send a message from the web user
     */
    public function sendUserMessage(string $sessionId, string $message): array
    {
        $session = ChatSession::find($sessionId);

        if (!$session) {
            return ['success' => false, 'error' => 'session_not_found'];
        }

        if ($session->status === ChatSession::STATUS_CLOSED) {
            return ['success' => false, 'error' => 'session_closed'];
        }

        // Check for timeout
        $timeoutMinutes = $this->settings->get('webchat_timeout_minutes') ?? 30;
        if ($session->hasTimedOut($timeoutMinutes)) {
            $session->status = ChatSession::STATUS_CLOSED;
            $session->save();
            return ['success' => false, 'error' => 'session_timeout'];
        }

        // Add the message
        $session->addMessage($message, 'user');

        // If session is active (has a volunteer), forward via SMS
        if ($session->status === ChatSession::STATUS_ACTIVE && $session->volunteer_phone) {
            try {
                $callHandling = $this->configService->getCallHandling($session->service_body_id);
                $callerId = $this->getOutboundCallerId($callHandling);

                $this->twilioService->client()->messages->create(
                    $session->volunteer_phone,
                    [
                        'from' => $callerId,
                        'body' => "[Web Chat] " . $message,
                    ]
                );

                Log::info("Chat session {$sessionId}: User message forwarded to volunteer");
            } catch (\Exception $e) {
                Log::error("Chat session {$sessionId}: Failed to forward message: " . $e->getMessage());
            }
        }

        return ['success' => true];
    }

    /**
     * Receive a reply from a volunteer via SMS webhook
     */
    public function receiveVolunteerReply(string $volunteerPhone, string $message, string $twilioNumber): array
    {
        // Find active or pending session for this volunteer
        $session = ChatSession::where('volunteer_phone', $volunteerPhone)
            ->whereIn('status', [ChatSession::STATUS_PENDING, ChatSession::STATUS_ACTIVE])
            ->orderBy('last_activity_at', 'desc')
            ->first();

        // If no active session, check if this is a response to a pending session
        if (!$session) {
            // Find any pending session for the service body associated with this Twilio number
            // The volunteer is responding to a blast
            $session = $this->findPendingSessionForVolunteer($volunteerPhone, $twilioNumber);
        }

        if (!$session) {
            Log::debug("No active chat session found for volunteer {$volunteerPhone}");
            return ['success' => false, 'error' => 'no_session'];
        }

        // Handle special commands
        $trimmedMessage = strtolower(trim($message));
        if ($trimmedMessage === 'end' || $trimmedMessage === 'close' || $trimmedMessage === 'done') {
            return $this->closeSession($session->id, 'volunteer');
        }

        // If session is pending, this volunteer is claiming it
        if ($session->status === ChatSession::STATUS_PENDING) {
            $session->volunteer_phone = $volunteerPhone;
            $session->status = ChatSession::STATUS_ACTIVE;
            $session->save();

            // Add system message
            $session->addMessage('A volunteer has joined the chat.', 'system');

            Log::info("Chat session {$session->id}: Claimed by volunteer {$volunteerPhone}");
        }

        // Add the volunteer's message
        $volunteerName = $this->getVolunteerName($volunteerPhone, $session->service_body_id);
        $session->addMessage($message, 'volunteer', $volunteerName);

        Log::info("Chat session {$session->id}: Message received from volunteer");

        return ['success' => true, 'session_id' => $session->id];
    }

    /**
     * Get messages for polling
     */
    public function getMessages(string $sessionId, ?string $since = null): array
    {
        $session = ChatSession::find($sessionId);

        if (!$session) {
            return ['success' => false, 'error' => 'session_not_found'];
        }

        // Check for timeout
        $timeoutMinutes = $this->settings->get('webchat_timeout_minutes') ?? 30;
        if ($session->hasTimedOut($timeoutMinutes)) {
            if ($session->status !== ChatSession::STATUS_CLOSED) {
                $session->status = ChatSession::STATUS_CLOSED;
                $session->addMessage('Session ended due to inactivity.', 'system');
                $session->save();
            }
        }

        return [
            'success' => true,
            'session_id' => $session->id,
            'status' => $session->status,
            'messages' => $session->getMessagesSince($since),
        ];
    }

    /**
     * Close a chat session
     */
    public function closeSession(string $sessionId, string $closedBy = 'user'): array
    {
        $session = ChatSession::find($sessionId);

        if (!$session) {
            return ['success' => false, 'error' => 'session_not_found'];
        }

        if ($session->status === ChatSession::STATUS_CLOSED) {
            return ['success' => true, 'already_closed' => true];
        }

        $session->status = ChatSession::STATUS_CLOSED;

        $closeMessage = $closedBy === 'volunteer'
            ? 'The volunteer has ended the chat.'
            : 'You have ended the chat.';

        $session->addMessage($closeMessage, 'system');
        $session->save();

        // Notify the volunteer if closed by user and session was active
        if ($closedBy === 'user' && $session->volunteer_phone) {
            try {
                $callHandling = $this->configService->getCallHandling($session->service_body_id);
                $callerId = $this->getOutboundCallerId($callHandling);

                $this->twilioService->client()->messages->create(
                    $session->volunteer_phone,
                    [
                        'from' => $callerId,
                        'body' => "[Web Chat] The user has ended the chat session.",
                    ]
                );
            } catch (\Exception $e) {
                Log::error("Chat session {$sessionId}: Failed to notify volunteer of close: " . $e->getMessage());
            }
        }

        Log::info("Chat session {$sessionId}: Closed by {$closedBy}");

        return ['success' => true];
    }

    /**
     * Get session by client ID
     */
    public function getSessionByClientId(string $clientId): ?ChatSession
    {
        return ChatSession::findByClientId($clientId);
    }

    /**
     * Format the notification SMS sent to volunteers
     */
    protected function formatVolunteerNotification(ChatSession $session, string $message, string $location): string
    {
        $prefix = $this->settings->get('webchat_volunteer_sms_prefix')
            ?? 'New web chat request';

        return sprintf(
            "[%s] from %s: \"%s\" - Reply to this message to connect. Send END to decline.",
            $prefix,
            $location,
            $message
        );
    }

    /**
     * Get outbound caller ID for SMS
     */
    protected function getOutboundCallerId($callHandling): string
    {
        if ($callHandling && isset($callHandling->forced_caller_id) && !empty($callHandling->forced_caller_id)) {
            return $callHandling->forced_caller_id;
        }

        return $this->settings->get('twilio_number') ?? '';
    }

    /**
     * Find pending session for a volunteer responding to a blast
     */
    protected function findPendingSessionForVolunteer(string $volunteerPhone, string $twilioNumber): ?ChatSession
    {
        // Get service bodies where this volunteer is active
        // For now, find any pending session that was recently created (within last 10 minutes)
        return ChatSession::where('status', ChatSession::STATUS_PENDING)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get volunteer name from their phone number
     */
    protected function getVolunteerName(string $phone, int $serviceBodyId): ?string
    {
        try {
            $volunteers = $this->volunteerService->getVolunteers($serviceBodyId);
            foreach ($volunteers as $volunteer) {
                if (isset($volunteer->volunteer_phone_number) && $volunteer->volunteer_phone_number === $phone) {
                    return $volunteer->volunteer_name ?? null;
                }
            }
        } catch (\Exception $e) {
            Log::debug("Could not find volunteer name for {$phone}: " . $e->getMessage());
        }

        return null;
    }
}
