import React, {useState} from "react";
import ServiceBodiesDropdown from "../components/ServiceBodiesDropdown";
import VolunteerControl from "../components/VolunteerControl";

function Volunteers() {
    const [serviceBodyId, setServiceBodyId] = useState(0);

    const serviceBodiesHandleChange = (event, index) => {
        console.log(event)
        setServiceBodyId(event)
    }

    return (
        <div>
            <ServiceBodiesDropdown handleChange={serviceBodiesHandleChange}/>
            {serviceBodyId > 0 ?
            <VolunteerControl serviceBodyId={serviceBodyId} /> : ""}
        </div>
    )
}

export default Volunteers;
