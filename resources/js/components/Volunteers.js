import ServiceBodiesDropdown from "./ServiceBodiesDropdown";
import React from "react";

function Volunteers() {

    const serviceBodiesHandleChange = (event, index) => {
        console.log(event)
    }

    return (
        <div>
            <ServiceBodiesDropdown handleChange={serviceBodiesHandleChange}/>
        </div>
    )
}

export default Volunteers;
