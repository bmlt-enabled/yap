<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\NoVolunteersException;
use App\Http\Controllers\Controller;
use App\Services\VolunteerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VolunteerScheduleController extends Controller
{
    protected VolunteerService $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/volunteers/schedule",
     *     tags={"Volunteers"},
     *     summary="Get volunteer helpline schedule",
     *     @OA\Parameter(
     *         name="service_body_id",
     *         in="query",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Volunteer schedule retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="number", type="string"),
     *                 @OA\Property(property="gender", type="string"),
     *                 @OA\Property(property="responder", type="boolean"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="language", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="notes", type="string"),
     *                 @OA\Property(property="shift_info", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request) : JsonResponse
    {
        try {
            $data = $this->volunteerService->getHelplineSchedule($request->get("service_body_id"), true);
        } catch (NoVolunteersException) {
            $data = [];
        }

        return response()->json($data)->header("Content-Type", "application/json");
    }
}
