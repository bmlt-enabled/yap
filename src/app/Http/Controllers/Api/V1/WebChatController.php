<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ChatSessionService;
use App\Services\GeocodingService;
use App\Services\MeetingResultsService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="WebChat",
 *     description="Web chat endpoints for browser-based text communication with volunteers"
 * )
 */
class WebChatController extends Controller
{
    protected SettingsService $settings;
    protected ChatSessionService $chatService;
    protected GeocodingService $geocodingService;
    protected MeetingResultsService $meetingResultsService;

    public function __construct(
        SettingsService $settings,
        ChatSessionService $chatService,
        GeocodingService $geocodingService,
        MeetingResultsService $meetingResultsService
    ) {
        $this->settings = $settings;
        $this->chatService = $chatService;
        $this->geocodingService = $geocodingService;
        $this->meetingResultsService = $meetingResultsService;
    }

    /**
     * Get webchat configuration
     *
     * @OA\Get(
     *     path="/api/v1/webchat/config",
     *     tags={"WebChat"},
     *     summary="Get webchat widget configuration",
     *     description="Returns configuration settings for the webchat widget",
     *     @OA\Response(
     *         response=200,
     *         description="Configuration retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="enabled", type="boolean"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="language", type="string"),
     *             @OA\Property(property="timeout_minutes", type="integer"),
     *             @OA\Property(property="meeting_search_enabled", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Webchat is not enabled"
     *     )
     * )
     */
    public function config(Request $request): JsonResponse
    {
        if (!$this->isWebchatEnabled()) {
            return response()->json([
                'error' => 'Webchat is not enabled'
            ], 403);
        }

        return response()->json([
            'enabled' => true,
            'title' => $this->settings->get('title') ?: 'Helpline',
            'language' => $this->settings->get('language') ?: 'en-US',
            'timeout_minutes' => $this->settings->get('webchat_timeout_minutes') ?? 30,
            'meeting_search_enabled' => $this->settings->get('webchat_meeting_search_enabled') ?? true,
        ]);
    }

    /**
     * Start a new chat session
     *
     * @OA\Post(
     *     path="/api/v1/webchat/session",
     *     tags={"WebChat"},
     *     summary="Start a new chat session with volunteers",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "location", "message"},
     *             @OA\Property(property="client_id", type="string", description="Unique client identifier"),
     *             @OA\Property(property="location", type="string", description="User's location (address or city)"),
     *             @OA\Property(property="message", type="string", description="Initial message to volunteers"),
     *             @OA\Property(property="latitude", type="number", description="Optional pre-geocoded latitude"),
     *             @OA\Property(property="longitude", type="number", description="Optional pre-geocoded longitude")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="session_id", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Webchat is not enabled"
     *     )
     * )
     */
    public function createSession(Request $request): JsonResponse
    {
        if (!$this->isWebchatEnabled()) {
            return response()->json([
                'error' => 'Webchat is not enabled'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|string|max:255',
            'location' => 'required|string|max:500',
            'message' => 'required|string|max:2000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $clientId = $request->input('client_id');
        $location = $request->input('location');
        $message = $request->input('message');

        // Check for existing session
        $existingSession = $this->chatService->getSessionByClientId($clientId);
        if ($existingSession && $existingSession->status !== 'closed') {
            return response()->json([
                'success' => true,
                'session_id' => $existingSession->id,
                'message' => 'Reconnected to existing session.',
                'resumed' => true,
            ]);
        }

        // Get coordinates
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        if (!$latitude || !$longitude) {
            try {
                $coordinates = $this->geocodingService->getCoordinatesForAddress($location);
                if ($coordinates && isset($coordinates->latitude) && isset($coordinates->longitude)) {
                    $latitude = $coordinates->latitude;
                    $longitude = $coordinates->longitude;
                }
            } catch (\Exception $e) {
                Log::warning("Geocoding failed for location: {$location}");
            }
        }

        if (!$latitude || !$longitude) {
            return response()->json([
                'success' => false,
                'error' => 'geocoding_failed',
                'message' => 'Could not determine your location. Please enter a valid address or city.',
            ], 400);
        }

        $result = $this->chatService->createSession(
            $clientId,
            (float) $latitude,
            (float) $longitude,
            $location,
            $message
        );

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Send a message in an existing session
     *
     * @OA\Post(
     *     path="/api/v1/webchat/session/{sessionId}/message",
     *     tags={"WebChat"},
     *     summary="Send a message in an existing chat session",
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", description="Message content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message sent successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or session issue"
     *     )
     * )
     */
    public function sendMessage(Request $request, string $sessionId): JsonResponse
    {
        if (!$this->isWebchatEnabled()) {
            return response()->json([
                'error' => 'Webchat is not enabled'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $result = $this->chatService->sendUserMessage($sessionId, $request->input('message'));

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }

    /**
     * Get messages from a session (for polling)
     *
     * @OA\Get(
     *     path="/api/v1/webchat/session/{sessionId}/messages",
     *     tags={"WebChat"},
     *     summary="Get messages from a chat session",
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="since",
     *         in="query",
     *         required=false,
     *         description="ISO 8601 timestamp to get messages since",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="session_id", type="string"),
     *             @OA\Property(property="status", type="string", enum={"pending", "active", "closed"}),
     *             @OA\Property(
     *                 property="messages",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="sender", type="string"),
     *                     @OA\Property(property="sender_name", type="string"),
     *                     @OA\Property(property="timestamp", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getMessages(Request $request, string $sessionId): JsonResponse
    {
        if (!$this->isWebchatEnabled()) {
            return response()->json([
                'error' => 'Webchat is not enabled'
            ], 403);
        }

        $since = $request->query('since');
        $result = $this->chatService->getMessages($sessionId, $since);

        $statusCode = $result['success'] ? 200 : 404;
        return response()->json($result, $statusCode);
    }

    /**
     * Close a chat session
     *
     * @OA\Post(
     *     path="/api/v1/webchat/session/{sessionId}/close",
     *     tags={"WebChat"},
     *     summary="Close a chat session",
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session closed successfully"
     *     )
     * )
     */
    public function closeSession(Request $request, string $sessionId): JsonResponse
    {
        if (!$this->isWebchatEnabled()) {
            return response()->json([
                'error' => 'Webchat is not enabled'
            ], 403);
        }

        $result = $this->chatService->closeSession($sessionId, 'user');

        return response()->json($result);
    }

    /**
     * Get session by client ID (for reconnection)
     *
     * @OA\Get(
     *     path="/api/v1/webchat/session",
     *     tags={"WebChat"},
     *     summary="Get existing session by client ID",
     *     @OA\Parameter(
     *         name="client_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session found or not found"
     *     )
     * )
     */
    public function getSession(Request $request): JsonResponse
    {
        if (!$this->isWebchatEnabled()) {
            return response()->json([
                'error' => 'Webchat is not enabled'
            ], 403);
        }

        $clientId = $request->query('client_id');

        if (!$clientId) {
            return response()->json([
                'success' => false,
                'error' => 'client_id required',
            ], 400);
        }

        $session = $this->chatService->getSessionByClientId($clientId);

        if (!$session) {
            return response()->json([
                'success' => true,
                'session' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'created_at' => $session->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Search for meetings near a location
     *
     * @OA\Get(
     *     path="/api/v1/webchat/meetings",
     *     tags={"WebChat"},
     *     summary="Search for meetings near a location",
     *     @OA\Parameter(
     *         name="location",
     *         in="query",
     *         required=true,
     *         description="Location to search near (city, address, or zip code)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Maximum number of meetings to return (default: 10)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Meetings found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="location", type="string"),
     *             @OA\Property(
     *                 property="meetings",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="meeting_name", type="string"),
     *                     @OA\Property(property="weekday_tinyint", type="integer"),
     *                     @OA\Property(property="start_time", type="string"),
     *                     @OA\Property(property="location_text", type="string"),
     *                     @OA\Property(property="location_street", type="string"),
     *                     @OA\Property(property="formats", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or geocoding failed"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Webchat or meeting search is not enabled"
     *     )
     * )
     */
    public function searchMeetings(Request $request): JsonResponse
    {
        if (!$this->isWebchatEnabled()) {
            return response()->json([
                'error' => 'Webchat is not enabled'
            ], 403);
        }

        if (!($this->settings->get('webchat_meeting_search_enabled') ?? true)) {
            return response()->json([
                'error' => 'Meeting search is not enabled'
            ], 403);
        }

        $location = $request->query('location');
        $limit = min((int) ($request->query('limit') ?? 10), 25); // Cap at 25

        if (!$location) {
            return response()->json([
                'success' => false,
                'error' => 'location required',
            ], 400);
        }

        // Geocode the location
        try {
            $coordinates = $this->geocodingService->getCoordinatesForAddress($location);
            if (!$coordinates || !isset($coordinates->latitude) || !isset($coordinates->longitude)) {
                return response()->json([
                    'success' => false,
                    'error' => 'geocoding_failed',
                    'message' => 'Could not find this location. Please try a different address or city.',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::warning("Geocoding failed for meeting search location: {$location}");
            return response()->json([
                'success' => false,
                'error' => 'geocoding_failed',
                'message' => 'Could not find this location. Please try a different address or city.',
            ], 400);
        }

        try {
            $meetingResults = $this->meetingResultsService->getMeetings(
                $coordinates->latitude,
                $coordinates->longitude,
                $limit
            );

            // Transform meetings to a simpler format for the widget
            $meetings = [];
            foreach (array_slice($meetingResults->filteredList, 0, $limit) as $meeting) {
                $meetings[] = [
                    'meeting_name' => $meeting->meeting_name ?? '',
                    'weekday_tinyint' => $meeting->weekday_tinyint ?? '',
                    'start_time' => $meeting->start_time ?? '',
                    'duration_time' => $meeting->duration_time ?? '',
                    'location_text' => $meeting->location_text ?? '',
                    'location_street' => $meeting->location_street ?? '',
                    'location_city_subsection' => $meeting->location_city_subsection ?? '',
                    'location_municipality' => $meeting->location_municipality ?? '',
                    'location_province' => $meeting->location_province ?? '',
                    'location_postal_code_1' => $meeting->location_postal_code_1 ?? '',
                    'formats' => $meeting->formats ?? '',
                    'virtual_meeting_link' => $meeting->virtual_meeting_link ?? '',
                    'phone_meeting_number' => $meeting->phone_meeting_number ?? '',
                    'latitude' => $meeting->latitude ?? null,
                    'longitude' => $meeting->longitude ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'location' => $location,
                'meetings' => $meetings,
                'total_found' => $meetingResults->originalListCount ?? count($meetings),
            ]);
        } catch (\Exception $e) {
            Log::error("Meeting search failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'search_failed',
                'message' => 'Failed to search for meetings. Please try again.',
            ], 500);
        }
    }

    /**
     * Check if webchat is enabled
     */
    protected function isWebchatEnabled(): bool
    {
        return $this->settings->has('webchat_enabled') && $this->settings->get('webchat_enabled');
    }
}
