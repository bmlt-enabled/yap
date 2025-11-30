<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\NoVolunteersException;
use App\Http\Controllers\Controller;
use App\Services\VolunteerService;
use Illuminate\Http\Request;

class VolunteerDownloadController extends Controller
{
    protected VolunteerService $volunteerService;

    public function __construct(VolunteerService $volunteerService)
    {
        $this->volunteerService = $volunteerService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/volunteers/download",
     *     tags={"Volunteers"},
     *     summary="Download volunteer list",
     *     @OA\Parameter(
     *         name="service_body_id",
     *         in="query",
     *         required=true,
     *         description="ID of the service body",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="recurse",
     *         in="query",
     *         required=false,
     *         description="Whether to include volunteers from child service bodies",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="fmt",
     *         in="query",
     *         required=false,
     *         description="Output format (csv or json)",
     *         @OA\Schema(type="string", enum={"csv", "json"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Volunteer list retrieved successfully",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="number", type="string"),
     *                         @OA\Property(property="gender", type="string"),
     *                         @OA\Property(property="responder", type="boolean"),
     *                         @OA\Property(property="type", type="string"),
     *                         @OA\Property(property="language", type="array", @OA\Items(type="string")),
     *                         @OA\Property(property="notes", type="string"),
     *                         @OA\Property(property="service_body_id", type="integer"),
     *                         @OA\Property(property="shift_info", type="object")
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="string",
     *                     format="binary",
     *                     description="CSV file download"
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $report = $this->volunteerService->getVolunteersListReport($request->get("service_body_id"), $request->get("recurse") ?? false);
            if ($request->get('fmt') == "csv") {
                $handle = fopen('php://memory', 'rw');
                try {
                    fputcsv($handle, array("name", "number", "gender", "responder", "type", "language", "notes", "service_body_id", "shift_info"));
                    foreach ($report as $item) {
                            fputcsv($handle, array(
                            $item->name,
                            $item->number,
                            $item->gender,
                            $item->responder,
                            $item->type,
                            json_encode($item->language),
                            $item->notes,
                            $item->service_body_id,
                            json_encode($item->shift_info)
                            ));
                    }
                    fseek($handle, 0);
                    $data = stream_get_contents($handle);
                    return response($data)
                        ->header("Content-Type", "text/plain")
                        ->header("Content-Length", strlen($data))
                        ->header("Content-Disposition", sprintf(
                            'attachment; filename="%s-volunteer-list.csv"',
                            $request->get("service_body_id")
                        ));
                } finally {
                    fclose($handle);
                }
            } elseif ($request->get('fmt') == "json") {
                return response()->json($report)->header("Content-Type", "application/json");
            } else {
                return response()->json()->header("Content-Type", "application/json");
            }
        } catch (NoVolunteersException $nve) {
            return response()->json()->header("Content-Type", "application/json");
        }
    }
}
