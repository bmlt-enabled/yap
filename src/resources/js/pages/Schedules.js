import ServiceBodiesDropdown from "../components/ServiceBodiesDropdown";
import React, { useState, useEffect, useRef } from "react";
import apiClient from '../services/api';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import '../../css/app.css';

// Add keyframe animation
const spinKeyframes = `
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}`;

// Create and append style element
const styleSheet = document.createElement("style");
styleSheet.textContent = spinKeyframes;
document.head.appendChild(styleSheet);

function Schedules() {
    const [serviceBodyId, setServiceBodyId] = useState(0);
    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(false);
    const calendarRef = useRef(null);

    const fetchSchedule = async (id) => {
        if (!id) return;
        
        setLoading(true);
        try {
            const response = await apiClient.get(`${rootUrl}/api/v1/volunteers/schedule?service_body_id=${id}`);
            console.log('Schedule response:', response.data);
            const scheduleData = response.data.map(event => ({
                ...event,
                start: new Date(event.start),
                end: new Date(event.end),
                display: 'block',
                backgroundColor: '#4a5568',
                borderColor: '#2d3748',
                textColor: '#ffffff'
            }));
            setEvents(scheduleData);
        } catch (error) {
            console.error('Error fetching schedule:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleServiceBodyChange = (id) => {
        setServiceBodyId(id);
        fetchSchedule(id);
    };

    // Cleanup function
    useEffect(() => {
        return () => {
            if (calendarRef.current) {
                const calendarApi = calendarRef.current.getApi();
                if (calendarApi) {
                    calendarApi.destroy();
                }
            }
        };
    }, []);

    return (
        <div className="calendar-container">
            <div className="calendar-header mb-4">
                <ServiceBodiesDropdown handleChange={handleServiceBodyChange} />
            </div>
            
            {loading ? (
                <div className="text-center py-8">
                    <div className="loading-spinner"></div>
                    <div className="loading-text">Loading schedule...</div>
                </div>
            ) : serviceBodyId > 0 ? (
                <div className="calendar-wrapper">
                    <FullCalendar
                        ref={calendarRef}
                        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
                        initialView="timeGridWeek"
                        firstDay={(new Date()).getDay()}
                        headerToolbar={{
                            left: null,
                            center: 'title',
                            right: 'timeGridWeek,timeGridDay,prev,next'
                        }}
                        events={events}
                        eventOrder={["sequence"]}
                        height="auto"
                        slotMinTime="00:00:00"
                        slotMaxTime="24:00:00"
                        slotEventOverlap={false}
                        allDaySlot={false}
                        nowIndicator={true}
                        eventTimeFormat={{
                            hour: 'numeric',
                            minute: '2-digit',
                            meridiem: 'short'
                        }}
                        eventDisplay="block"
                        // validRange={{
                        //     start: new Date().toISOString().split('T')[0],
                        //     end: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
                        // }}
                        eventContent={(eventInfo) => {
                            return {
                                html: `
                                    <div class="fc-event-main-frame">
                                        <div class="fc-event-title-container">
                                            <div class="fc-event-title">${eventInfo.event.title}</div>
                                        </div>
                                        <div class="fc-event-time">
                                            ${eventInfo.timeText}
                                        </div>
                                    </div>
                                `
                            };
                        }}
                        slotLabelFormat={{
                            hour: 'numeric',
                            minute: '2-digit',
                            meridiem: 'short'
                        }}
                        dayHeaderFormat={{
                            weekday: 'short',
                            month: 'short',
                            day: 'numeric'
                        }}
                        expandRows={true}
                        stickyHeaderDates={true}
                        dayMaxEvents={true}
                        moreLinkContent={(args) => `+${args.num} more`}
                    />
                </div>
            ) : (
                <div className="empty-state">
                    Please select a service body to view the schedule
                </div>
            )}
        </div>
    );
}

export default Schedules;
