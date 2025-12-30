<?php

namespace App\Http\Controllers;

use App\Constants\EventId;
use App\Services\CallService;
use App\Services\ConfigService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

class WebRtcCallController extends Controller
{
    protected SettingsService $settings;
    protected CallService $call;
    protected ConfigService $config;

    public function __construct(
        SettingsService $settings,
        CallService     $call,
        ConfigService   $config
    ) {
        $this->settings = $settings;
        $this->call = $call;
        $this->config = $config;
    }

    /**
     * Handle incoming WebRTC call from browser
     * This is the TwiML Application webhook endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleCall(Request $request)
    {
        Log::info('WebRTC call received', [
            'from' => $request->input('From'),
            'callSid' => $request->input('CallSid'),
            'serviceBodyId' => $request->input('serviceBodyId'),
            'searchType' => $request->input('searchType'),
            'location' => $request->input('location'),
        ]);

        $twiml = new VoiceResponse();

        // Check if WebRTC is enabled
        if (!$this->settings->has('webrtc_enabled') || !$this->settings->get('webrtc_enabled')) {
            $twiml->say('WebRTC calling is not enabled. Goodbye.')
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get('language'));
            $twiml->hangup();
            return response($twiml)->header('Content-Type', 'text/xml; charset=utf-8');
        }

        // Get parameters from the browser
        $serviceBodyId = $request->input('serviceBodyId');
        $searchType = $request->input('searchType', 'helpline');
        $location = $request->input('location');

        // Build redirect URL based on search type
        $baseUrl = url('/');
        $params = [];

        // Mark this as a WebRTC call
        $params['webrtc'] = '1';

        // If service body is specified, use it
        if ($serviceBodyId) {
            $params['override_service_body_id'] = $serviceBodyId;
        }

        // Handle different search types
        switch ($searchType) {
            case 'helpline':
                // Direct to helpline search with location if provided
                if ($location) {
                    // Store location in session and redirect to address lookup
                    session()->put('webrtc_location', $location);
                    $redirectUrl = $baseUrl . '/address-lookup?' . http_build_query($params);
                    $twiml->redirect($redirectUrl);
                } else {
                    // Go to normal IVR flow
                    $redirectUrl = $baseUrl . '/?' . http_build_query($params);
                    $twiml->redirect($redirectUrl);
                }
                break;

            case 'meeting':
                // Direct to meeting search
                if ($location) {
                    session()->put('webrtc_location', $location);
                    $params['SearchType'] = '2'; // Meeting search
                    $redirectUrl = $baseUrl . '/address-lookup?' . http_build_query($params);
                    $twiml->redirect($redirectUrl);
                } else {
                    $params['SearchType'] = '2';
                    $redirectUrl = $baseUrl . '/?' . http_build_query($params);
                    $twiml->redirect($redirectUrl);
                }
                break;

            case 'jft':
                // Direct to Just For Today
                $redirectUrl = $baseUrl . '/fetch-jft?' . http_build_query($params);
                $twiml->redirect($redirectUrl);
                break;

            case 'spad':
                // Direct to SPAD
                $redirectUrl = $baseUrl . '/fetch-spad?' . http_build_query($params);
                $twiml->redirect($redirectUrl);
                break;

            default:
                // Default: go to main IVR
                $redirectUrl = $baseUrl . '/?' . http_build_query($params);
                $twiml->redirect($redirectUrl);
                break;
        }

        return response($twiml)->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Handle WebRTC call status callbacks
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function statusCallback(Request $request)
    {
        Log::info('WebRTC call status callback', [
            'callSid' => $request->input('CallSid'),
            'callStatus' => $request->input('CallStatus'),
        ]);

        // Return empty TwiML response
        $twiml = new VoiceResponse();
        return response($twiml)->header('Content-Type', 'text/xml; charset=utf-8');
    }
}
