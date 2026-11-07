<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthorizationService;
use App\Services\WebhookFailureService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="System Health",
 *     description="Runtime health of the Twilio webhooks"
 * )
 *
 * Twilio webhook 5xx history for System Health (bmlt-enabled/yap#1686).
 *
 * Failures span every service body, so only top-level admins can read them.
 */
class WebhookFailuresController extends Controller
{
    public function __construct(
        private WebhookFailureService $failures,
        private AuthorizationService $authorization,
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/webhookFailures",
     *     tags={"System Health"},
     *     summary="Recent 5xx responses from Twilio-facing webhooks",
     *     description="Counts for the last 24 hours and 7 days, and the most recent failures with the events yap recorded for each call. Top-level admins only.",
     *     @OA\Response(
     *         response=200,
     *         description="Webhook failure summary",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="retention_days", type="integer", example=30),
     *             @OA\Property(property="last_24_hours", type="integer"),
     *             @OA\Property(property="last_7_days", type="integer"),
     *             @OA\Property(property="last_failure_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(
     *                 property="failures",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="method", type="string", example="POST"),
     *                     @OA\Property(property="route", type="string", example="/address-lookup.php"),
     *                     @OA\Property(property="status", type="integer", example=500),
     *                     @OA\Property(property="callsid", type="string", nullable=true),
     *                     @OA\Property(property="exception_class", type="string", nullable=true),
     *                     @OA\Property(property="message", type="string", nullable=true),
     *                     @OA\Property(property="location", type="string", nullable=true),
     *                     @OA\Property(
     *                         property="events",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="event_id", type="integer"),
     *                             @OA\Property(property="event_name", type="string"),
     *                             @OA\Property(property="event_time", type="string", format="date-time"),
     *                             @OA\Property(property="service_body_id", type="integer", nullable=true)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Not a top-level admin")
     * )
     */
    public function index(): JsonResponse
    {
        if (!$this->authorization->isTopLevelAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($this->failures->summary());
    }
}
