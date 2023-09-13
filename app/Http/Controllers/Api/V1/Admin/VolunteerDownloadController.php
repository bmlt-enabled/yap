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

    public function index(Request $request)
    {
        try {
            $report = $this->volunteerService->getVolunteersListReport($request->get("service_body_id"));
            if ($request->get('fmt') == "csv") {
                $handle = fopen('php://memory', 'rw');
                try {
                    fputcsv($handle, array("name", "number", "gender", "responder", "type", "language", "shift_info"));
                    foreach ($report as $item) {
                        fputcsv($handle, array(
                            $item->name,
                            $item->number,
                            $item->gender,
                            $item->responder,
                            $item->type,
                            json_encode($item->language),
                            json_encode($item->shift_info)
                        ));
                    }
                    fseek($handle, 0);
                    $data = stream_get_contents($handle);
                    return response($data)
                        ->header("Content-Type", "text/plain")
                        ->header("Content-Length", strlen($data))
                        ->header("Content-Disposition", sprintf(
                            'attachment; filename="%s-map-metrics.csv"',
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
