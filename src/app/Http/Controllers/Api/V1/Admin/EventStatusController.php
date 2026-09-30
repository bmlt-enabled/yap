<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\EventId;
use App\Constants\EventStatusId;
use App\Http\Controllers\Controller;
use App\Models\EventStatus;
use App\Repositories\VoicemailRepository;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Twilio\Exceptions\RestException;

class EventStatusController extends Controller
{
    private AuthorizationService $permissionService;
    private EventStatus $eventStatus;
    private VoicemailRepository $voicemail;

    public function __construct(
        AuthorizationService $permissionService,
        EventStatus $eventStatus,
        VoicemailRepository $voicemail
    ) {
        $this->permissionService = $permissionService;
        $this->eventStatus = $eventStatus;
        $this->voicemail = $voicemail;
    }

    public function index()
    {
        return response()->json($this->eventStatus::all())->header('Content-Type', 'application/json');
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->permissionService->callsid($request->callsid, $request->event_id)) {
            return response()->json(['error' => 'Unauthorized'], 403)->header('Content-Type', 'application/json');
        }

        if ((int) $request->event_id === EventId::VOICEMAIL
            && (int) $request->status === EventStatusId::VOICEMAIL_DELETED) {
            return $this->deleteVoicemail($request->callsid);
        }

        $eventStatus = $this->eventStatus::create($request->all());
        return response()->json($eventStatus, 201)->header('Content-Type', 'application/json');
    }

    private function deleteVoicemail(string $callsid): JsonResponse
    {
        try {
            $eventStatus = $this->voicemail->delete($callsid);
        } catch (RestException) {
            return response()->json(['error' => 'Could not delete the Twilio recording'], 502)
                ->header('Content-Type', 'application/json');
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422)
                ->header('Content-Type', 'application/json');
        }

        if ($eventStatus === null) {
            return response()->json(['error' => 'Voicemail not found'], 404)
                ->header('Content-Type', 'application/json');
        }

        return response()->json($eventStatus, 201)->header('Content-Type', 'application/json');
    }
}
