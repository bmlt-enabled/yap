<?php

namespace App\Http\Controllers;

use App\Constants\TwilioCallStatus;
use App\Services\FakeTwilio\FakeTwilioStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Control and inspection endpoints for fake Twilio mode, used by the call/SMS
 * simulator (php artisan yap:simulate).
 *
 * The simulator plays the part of Twilio's media side: it tells the fake account
 * about the inbound call it is placing and about conference joins/leaves that
 * real TwiML execution would cause, then reads back the outbound REST requests
 * the app made (calls placed, SMS sent, calls redirected or hung up).
 *
 * Only registered when fake mode is enabled, and only outside production. The
 * routes sit behind ValidateTwilioSignature like every other Twilio route, so
 * a caller needs the configured auth token.
 */
class FakeTwilioController extends Controller
{
    public function __construct(private FakeTwilioStore $store)
    {
    }

    public function requests(Request $request): JsonResponse
    {
        return response()->json([
            'requests' => $this->store->requests(
                (int) $request->get('after', 0),
                $request->get('resource'),
            ),
        ]);
    }

    public function reset(): JsonResponse
    {
        $this->store->reset();

        return response()->json(['reset' => true]);
    }

    public function registerCall(Request $request): JsonResponse
    {
        $request->validate([
            'sid' => 'required|string',
            'from' => 'required|string',
            'to' => 'required|string',
        ]);

        $sid = (string) $request->get('sid');
        $to = (string) $request->get('to');
        $attributes = ['status' => (string) $request->get('status', TwilioCallStatus::INPROGRESS)];

        // A known call (e.g. an outbound leg the app placed) only changes status;
        // anything new is an inbound call to one of the account's numbers.
        if ($this->store->call($sid) === null) {
            $attributes += [
                'from' => (string) $request->get('from'),
                'to' => $to,
                'direction' => 'inbound',
                'phone_number_sid' => (string) $request->get('phone_number_sid', 'PN' . md5($to)),
            ];
        }

        return response()->json(['call' => $this->store->saveCall($sid, $attributes)]);
    }

    public function call(string $sid): JsonResponse
    {
        $call = $this->store->call($sid);

        return $call === null
            ? response()->json(['error' => "Unknown call {$sid}"], 404)
            : response()->json(['call' => $call]);
    }

    public function joinConference(Request $request): JsonResponse
    {
        $request->validate(['friendly_name' => 'required|string', 'call_sid' => 'required|string']);
        $this->store->joinConference((string) $request->get('friendly_name'), (string) $request->get('call_sid'));

        return $this->conference((string) $request->get('friendly_name'));
    }

    public function leaveConference(Request $request): JsonResponse
    {
        $request->validate(['friendly_name' => 'required|string', 'call_sid' => 'required|string']);
        $this->store->leaveConference((string) $request->get('friendly_name'), (string) $request->get('call_sid'));

        return $this->conference((string) $request->get('friendly_name'));
    }

    private function conference(string $friendlyName): JsonResponse
    {
        return response()->json([
            'conference' => [
                'sid' => FakeTwilioStore::conferenceSid($friendlyName),
                'friendly_name' => $friendlyName,
                'participants' => $this->store->participants($friendlyName),
            ],
        ]);
    }
}
