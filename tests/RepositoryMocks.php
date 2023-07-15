<?php

namespace Tests;

use App\Constants\DataType;
use App\Models\VolunteerData;

class RepositoryMocks
{
    public function getVolunteersMock(
        $repository,
        $volunteerName,
        $volunteerGender,
        $volunteerResponder,
        $volunteerLanguages,
        $volunteerPhoneNumber,
        $numberOfShifts,
        $serviceBodyId,
        $parentServiceBodyId
    ) {
        $shiftTz = "America/New_York";
        $shiftStart = "12:00 AM";
        $shiftEnd = "11:59 PM";

        $shifts = [];
        for ($i = 1; $i <= $numberOfShifts; $i++) {
            $shifts[] = [
                "day" => $i,
                "tz" => $shiftTz,
                "start_time" => $shiftStart,
                "end_time" => $shiftEnd,
            ];
        }

        $volunteerData = new VolunteerData();
        $volunteerData->volunteer_name = $volunteerName;
        $volunteerData->volunteer_phone_number = $volunteerPhoneNumber;
        $volunteerData->volunteer_gender = $volunteerGender;
        $volunteerData->volunteer_responder = $volunteerResponder;
        $volunteerData->volunteer_languages = $volunteerLanguages;
        $volunteerData->volunteer_notes = "";
        $volunteerData->volunteer_enabled = true;
        $volunteerData->volunteer_shift_schedule = base64_encode(json_encode($shifts));

        $repository->shouldReceive("getDbData")->with(
            $serviceBodyId,
            DataType::YAP_VOLUNTEERS_V2
        )->andReturn([(object)[
            "service_body_id" => $serviceBodyId,
            "id" => "200",
            "parent_id" => $parentServiceBodyId,
            "data" => json_encode([$volunteerData])
        ]]);

        return $repository;
    }
}
