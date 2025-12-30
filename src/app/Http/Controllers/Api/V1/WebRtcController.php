<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;

class WebRtcController extends Controller
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Generate a Twilio Access Token for WebRTC calling
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function token(Request $request): JsonResponse
    {
        // Check if WebRTC is enabled
        if (!$this->settings->has('webrtc_enabled') || !$this->settings->get('webrtc_enabled')) {
            return response()->json([
                'error' => 'WebRTC calling is not enabled'
            ], 403);
        }

        // Validate required Twilio settings
        $accountSid = $this->settings->get('twilio_account_sid');
        $apiKey = $this->settings->get('twilio_api_key');
        $apiSecret = $this->settings->get('twilio_api_secret');
        $twimlAppSid = $this->settings->get('twilio_twiml_app_sid');

        if (empty($accountSid) || empty($apiKey) || empty($apiSecret) || empty($twimlAppSid)) {
            Log::error('WebRTC token generation failed: Missing Twilio configuration');
            return response()->json([
                'error' => 'WebRTC is not properly configured'
            ], 500);
        }

        try {
            // Generate a unique identity for this caller
            $identity = 'webrtc_' . uniqid();

            // Create access token
            $token = new AccessToken(
                $accountSid,
                $apiKey,
                $apiSecret,
                3600, // Token valid for 1 hour
                $identity
            );

            // Create Voice grant
            $voiceGrant = new VoiceGrant();
            $voiceGrant->setOutgoingApplicationSid($twimlAppSid);

            // Add grant to token
            $token->addGrant($voiceGrant);

            return response()->json([
                'token' => $token->toJWT(),
                'identity' => $identity,
                'expires_in' => 3600
            ]);
        } catch (\Exception $e) {
            Log::error('WebRTC token generation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate access token'
            ], 500);
        }
    }

    /**
     * Get WebRTC widget configuration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function config(Request $request): JsonResponse
    {
        if (!$this->settings->has('webrtc_enabled') || !$this->settings->get('webrtc_enabled')) {
            return response()->json([
                'error' => 'WebRTC calling is not enabled'
            ], 403);
        }

        return response()->json([
            'enabled' => true,
            'title' => $this->settings->get('title') ?: 'Helpline',
            'language' => $this->settings->get('language') ?: 'en-US',
        ]);
    }
}
