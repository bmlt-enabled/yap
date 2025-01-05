import React, {useState} from "react";
import ServiceBodiesDropdown from "../components/ServiceBodiesDropdown";
import VolunteerControl from "../components/VolunteerControl";
import {Button, ButtonGroup} from "@mui/material";

function Volunteers() {
    const [serviceBodyId, setServiceBodyId] = useState(0);

    const serviceBodiesHandleChange = (event, index) => {
        setServiceBodyId(event)
    }

    return (
        <div>
            <ServiceBodiesDropdown handleChange={serviceBodiesHandleChange}/>
            {serviceBodyId > 0 ?
            <ButtonGroup>
                <Button>Add Volunteer</Button>
                <Button>Save Volunteers</Button>
                <Button>Include Group</Button>
            </ButtonGroup> : ""}
            <VolunteerControl serviceBodyId={serviceBodyId}></VolunteerControl>
        </div>
    )
}

export default Volunteers;
