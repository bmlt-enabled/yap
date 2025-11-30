<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\VoicemailRepository;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Voicemails",
 *     description="API Endpoints for managing voicemails"
 * )
 */
class VoicemailController extends Controller
{
    private VoicemailRepository $voicemailRepository;

    public function __construct(VoicemailRepository $voicemailRepository)
    {
        $this->voicemailRepository = $voicemailRepository;
    }

    /**
     * Get voicemails for a service body
     *
     * @OA\Get(
     *     path="/api/v1/voicemail",
     *     summary="Get voicemails for a service body",
     *     description="Retrieves all voicemails associated with a specific service body",
     *     operationId="getVoicemails",
     *     tags={"Voicemails"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         description="ID of the service body",
     *         required=true,
     *         in="query",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="callsid", type="string", example="CA1234567890"),
     *                     @OA\Property(property="pin", type="string", example="1234"),
     *                     @OA\Property(property="from_number", type="string", example="+1234567890"),
     *                     @OA\Property(property="to_number", type="string", example="+1987654321"),
     *                     @OA\Property(property="event_time", type="string", format="date-time", example="2024-03-24T12:00:00Z"),
     *                     @OA\Property(property="meta", type="object", example={"duration": "30", "url": "https://example.com/voicemail.mp3"})
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service body not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service body not found.")
     *         )
     *     )
     * )
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $serviceBodyId = request()->query('serviceBodyId');
        
        if (!$serviceBodyId) {
            return response()->json([
                'message' => 'Service body ID is required'
            ], 400);
        }

        $voicemails = $this->voicemailRepository->get($serviceBodyId);
        
        return response()->json([
            'data' => $voicemails
        ]);
    }

    /**
     * Delete a voicemail
     *
     * @OA\Delete(
     *     path="/api/v1/voicemail/{voicemail}",
     *     summary="Delete a voicemail",
     *     description="Deletes a specific voicemail by its call SID",
     *     operationId="deleteVoicemail",
     *     tags={"Voicemails"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="serviceBodyId",
     *         description="ID of the service body",
     *         required=true,
     *         in="query",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="voicemail",
     *         description="Call SID of the voicemail to delete",
     *         required=true,
     *         in="path",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Voicemail deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Voicemail deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Voicemail not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Voicemail not found.")
     *         )
     *     )
     * )
     *
     * @param string $callSid
     * @return JsonResponse
     */
    public function destroy(string $callSid): JsonResponse
    {
        $serviceBodyId = request()->query('serviceBodyId');
        
        if (!$serviceBodyId) {
            return response()->json([
                'message' => 'Service body ID is required'
            ], 400);
        }

        $deleted = $this->voicemailRepository->delete($serviceBodyId, $callSid);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Voicemail not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Voicemail deleted successfully'
        ]);
    }
}
