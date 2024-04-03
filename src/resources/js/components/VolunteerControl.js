import {useEffect, useState} from "react";
import Box from "@mui/material/Box";
import {Card, TextField} from "@mui/material";

function VolunteerControl({serviceBodyId})
{
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);

    const getVolunteers = async() => {
        setLoading(true)
        let response = await fetch(`${rootUrl}/api/v1/config?service_body_id=${serviceBodyId}&data_type=_YAP_VOLUNTEERS_V2_`)
        let responseData = await response.json()
        setData(responseData)
        setLoading(false)
    }

    useEffect(() => {
        console.log(`volunteercontrol: ${serviceBodyId}`)
        getVolunteers()
    }, [])

    return (
        !loading && data != null ? data.data.map(volunteer => (
            <Card>
                <div>
                    <TextField label="Volunteer Name" defaultValue={volunteer.volunteer_name}/>
                    <TextField label="Phone Number" defaultValue={volunteer.volunteer_phone_number}/>
                </div>
            </Card>
        )) : ""
    );
}

export default VolunteerControl;
