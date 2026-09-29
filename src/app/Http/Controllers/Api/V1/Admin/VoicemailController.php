<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Constants\EventId;
use App\Http\Controllers\Controller;
use App\Repositories\VoicemailRepository;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Twilio\Exceptions\RestException;

class VoicemailController extends Controller
{
    private VoicemailRepository $voicemail;
    private AuthorizationService $permissionService;

    public function __construct(VoicemailRepository $voicemail, AuthorizationService $permissionService)
    {
        $this->voicemail = $voicemail;
        $this->permissionService = $permissionService;
    }

    public function destroyMany(Request $request): JsonResponse
    {
        $callsids = $request->input('callsids');
        if (!is_array($callsids) || count($callsids) === 0) {
            return response()->json(['error' => 'No voicemails selected'], 400)
                ->header('Content-Type', 'application/json');
        }

        $deleted = [];
        $failed = [];
        foreach ($callsids as $callsid) {
            if (!is_string($callsid) || $callsid === '') {
                $failed[] = ['callsid' => $callsid, 'error' => 'Invalid callsid'];
                continue;
            }

            if (!$this->permissionService->callsid($callsid, EventId::VOICEMAIL)) {
                $failed[] = ['callsid' => $callsid, 'error' => 'Unauthorized'];
                continue;
            }

            try {
                $status = $this->voicemail->delete($callsid);
            } catch (RestException) {
                $failed[] = ['callsid' => $callsid, 'error' => 'Could not delete the Twilio recording'];
                continue;
            } catch (RuntimeException $e) {
                $failed[] = ['callsid' => $callsid, 'error' => $e->getMessage()];
                continue;
            }

            if ($status === null) {
                $failed[] = ['callsid' => $callsid, 'error' => 'Voicemail not found'];
                continue;
            }

            $deleted[] = $callsid;
        }

        return response()->json([
            'deleted' => $deleted,
            'failed' => $failed,
        ])->header('Content-Type', 'application/json');
    }
}
