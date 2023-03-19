import {useEffect, useState} from "react";

function VolunteerControl({serviceBodyId}) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(false);

    const getVolunteers = async () => {
        setLoading(true)
        let response = await fetch(`${rootUrl}/api/v1/config?service_body_id=${serviceBodyId}&data_type=_YAP_VOLUNTEERS_V2_`)
        let responseData = await response.json()
        setData(responseData)
        setLoading(false)
    }

    useEffect(() => {
        console.log(`volutneercontrol: ${serviceBodyId}`)
        getVolunteers()
    }, [])

    return (
        !loading && data != null ? JSON.stringify(data) : ""
    )
}

export default VolunteerControl;
