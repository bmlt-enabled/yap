import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from "@fullcalendar/react";
import {useState} from "react";
import ServiceBodiesDropdown from "../components/ServiceBodiesDropdown";

function Schedules() {
    const [eventData, setEventData] = useState([]);

    const serviceBodyOnChange = async(id) => {
        let response = await fetch(`${rootUrl}/api/v1/volunteers/schedule?service_body_id=${id}`)
        let responseData = await response.json()
        setEventData(responseData)
    }

    return (
        <div>
            <ServiceBodiesDropdown handleChange={serviceBodyOnChange} />
            { }
            <FullCalendar
                plugins={[ timeGridPlugin ]}
                initialView="timeGridWeek"
                events={eventData}
            />
        </div>
    )
}

export default Schedules;
