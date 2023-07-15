<?php

namespace Tests;

class RepositoryMocks
{
    public function getVolunteersMock($volunteer_name, $volunteer_gender, $volunteer_responder, $volunteer_languages)
    {
        $shifts = [];
        for ($i = 1; $i <= 7; $i++) {
            $shifts[] = [
                "day" => $i,
                "tz" => $shiftTz,
                "start_time" => $shiftStart,
                "end_time" => $shiftEnd,
            ];
        }

        $volunteer = [[
            "volunteer_name"=>$volunteer_name,
            "volunteer_phone_number"=>"(555) 111-2222",
            "volunteer_gender"=>$volunteer_gender,
            "volunteer_responder"=>$volunteer_responder,
            "volunteer_languages"=>$volunteer_languages,
            "volunteer_notes"=>"",
            "volunteer_enabled"=>true,
            "volunteer_shift_schedule"=>base64_encode(json_encode($shifts))
        ]];
        $repository->shouldReceive("getDbData")->with(
            $this->serviceBodyId,
            DataType::YAP_VOLUNTEERS_V2
        )->andReturn([(object)[
            "service_body_id" => $this->serviceBodyId,
            "id" => "200",
            "parent_id" => $this->parentServiceBodyId,
            "data" => json_encode($volunteer)
        ]]);
    }
}
