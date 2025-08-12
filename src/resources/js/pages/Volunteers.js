import React, {useState} from "react";
import ServiceBodiesDropdown from "../components/ServiceBodiesDropdown";
import { useLocalization } from "../contexts/LocalizationContext";
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,     
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
    Modal,
    Box,
    Button,
    TextField,
    Select,
    MenuItem,
    InputLabel,
    FormControl,
    ButtonGroup,
    CircularProgress,
} from '@mui/material';
import apiClient from "../services/api";
import {defaultVolunteer} from "../models/VolunteerModel";
import {defaultShift} from "../models/ShiftModel";
import SortableVolunteer from "../components/SortableVolunteer";

    function Volunteers() {
    const { getWord, loading: localizationsLoading } = useLocalization();
    const [volunteers, setVolunteers] = useState([]);
    const [serviceBodyId, setServiceBodyId] = useState();
    const [showModal, setShowModal] = useState(false);
    const [currentVolunteer, setCurrentVolunteer] = useState();
    const [shiftData, setShiftData] = useState("");
    const [expanded, setExpanded] = useState({});
    const [loading, setLoading] = useState(false);
    const [timezones, setTimezones] = useState([]);
    const [currentTimezone, setCurrentTimezone] = useState('');
    const [timezoneOpen, setTimezoneOpen] = useState(false);
    const daysOfWeek = getWord('days_of_the_week');
       
    // Get current browser timezone
    React.useEffect(() => {
        const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        setCurrentTimezone(browserTimezone);
        
        // Set default timezone in shiftData
        setShiftData(prev => ({ ...prev, tz: browserTimezone }));
    }, []);

    // Fetch timezones from API
    React.useEffect(() => {
        const fetchTimezones = async () => {
            try {
                const response = await apiClient.get('/api/v1/settings/timezones');
                setTimezones(response.data);
            } catch (error) {
                console.error('Error fetching timezones:', error);
            }
        };
        
        fetchTimezones();
    }, []);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const serviceBodiesHandleChange = (event, index) => {
        setServiceBodyId(event)
        getVolunteers(event)
    }

    const handleAddVolunteer = () => {
        setVolunteers([...volunteers, {...defaultVolunteer}]);
    };

    const getVolunteers = async (serviceBodyId) => {
        setLoading(true);
        try {
            let response = await apiClient(`${rootUrl}/api/v1/volunteers?serviceBodyId=${serviceBodyId}`);
            let responseData = await response.data;            
            setVolunteers(responseData.data);
        } catch (error) {
            console.error('Error fetching volunteers:', error);
        } finally {
            setLoading(false);
        }
    };

    const saveVolunteers = async (event) => {
        setLoading(true);
        try {
            // Encode shift schedules before saving
            const encodedVolunteers = volunteers.map(volunteer => ({
                ...volunteer,
                volunteer_shift_schedule: btoa(JSON.stringify(volunteer.volunteer_shift_schedule))
            }));
            let response = await apiClient.post(`${rootUrl}/api/v1/volunteers?serviceBodyId=${serviceBodyId}`, encodedVolunteers);
            let responseData = await response.data;
            setVolunteers(responseData.data);
        } catch (error) {
            console.error('Error saving volunteers:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleAddShift = (volunteerIndex) => {
        setCurrentVolunteer(volunteerIndex);
        setShiftData({ ...defaultShift, tz: currentTimezone });
        setShowModal(true);
    };

    const saveShift = () => {
        const updatedVolunteers = [...volunteers];

        if (!Array.isArray(updatedVolunteers[currentVolunteer].volunteer_shift_schedule)) {
            updatedVolunteers[currentVolunteer].volunteer_shift_schedule = [];
        }

        updatedVolunteers[currentVolunteer].volunteer_shift_schedule.push(shiftData);
        setVolunteers(updatedVolunteers);
        setShowModal(false);
        setShiftData({ ...defaultShift, tz: currentTimezone });
        setTimezoneOpen(false);
    };

    const handleRemoveShift = (volunteerIndex, shiftIndex) => {
        const updatedVolunteers = [...volunteers];
        updatedVolunteers[volunteerIndex].volunteer_shift_schedule.splice(shiftIndex, 1);
        setVolunteers(updatedVolunteers);
    };  

    const handleRemoveAllShifts = (volunteerIndex) => {
        const updatedVolunteers = [...volunteers];
        updatedVolunteers[volunteerIndex].volunteer_shift_schedule = [];
        setVolunteers(updatedVolunteers);
    };

    const handleDragEnd = (event) => {
        const { active, over } = event;

        if (active.id !== over.id) {
            setVolunteers((items) => {
                const oldIndex = parseInt(active.id);
                const newIndex = parseInt(over.id);
                return arrayMove(items, oldIndex, newIndex);
            });
        }
    };

    const toggleExpand = (id) => {
        setExpanded(prev => ({ ...prev, [id]: !prev[id] }));
    };

    return (
        <div className="container">
            <div className="form-group">
                <ServiceBodiesDropdown handleChange={serviceBodiesHandleChange}/>
                {serviceBodyId > 0 ?
                    <ButtonGroup sx={{
                        padding: 2
                    }}>
                        <Button variant="contained" color="primary" onClick={handleAddVolunteer}>{getWord('add_volunteer')}</Button>
                        <Button variant="contained" color="success" onClick={saveVolunteers}>{getWord('save_volunteers')}</Button>
                        <Button variant="contained" color="warning">{getWord('include_group')}</Button>
                    </ButtonGroup> : ""}
            </div>

            {loading ? (
                <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '200px' }}>
                    <CircularProgress />
                </Box>
            ) : (
                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCenter}
                    onDragEnd={handleDragEnd}
                >
                    <SortableContext
                        items={volunteers.map((_, index) => index.toString())}
                        strategy={verticalListSortingStrategy}
                    >
                        {volunteers && volunteers.length > 0 && volunteers.map((volunteer, index) => (
                            <SortableVolunteer
                                key={index}
                                volunteer={volunteer}
                                index={index}
                                volunteers={volunteers}
                                setVolunteers={setVolunteers}
                                expanded={expanded}
                                toggleExpand={toggleExpand}
                                handleAddShift={handleAddShift}
                                handleRemoveShift={handleRemoveShift}
                                handleRemoveAllShifts={handleRemoveAllShifts}
                                daysOfWeek={daysOfWeek}
                                getWord={getWord}
                            />
                        ))}
                    </SortableContext>
                </DndContext>
            )}

            <Modal open={showModal} onClose={() => {
                setShowModal(false);
                setTimezoneOpen(false);
            }}>
                <Box sx={{
                    position: 'absolute',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    width: 400,
                    bgcolor: 'background.paper',
                    border: '2px solid #000',
                    boxShadow: 24,
                    p: 4
                }}>
                    <h2>{getWord('add_shift')}</h2>
                    <FormControl fullWidth margin="normal">
                        <InputLabel>{getWord('day_of_the_week')}</InputLabel>
                        <Select
                            value={shiftData.day}
                            onChange={e => setShiftData({ ...shiftData, day: e.target.value, day_name: daysOfWeek[e.target.value] })}
                            label={getWord('day_of_the_week')}
                        >
                            {daysOfWeek && Object.keys(daysOfWeek).map((key) => (
                                <MenuItem key={key} value={key}>{daysOfWeek[key]}</MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    <TextField
                        label={getWord('start_time')}
                        type="time"
                        value={shiftData.start_time}
                        onChange={e => setShiftData({ ...shiftData, start_time: e.target.value })}
                        fullWidth
                        margin="normal"
                    />
                    <TextField
                        label={getWord('end_time')}
                        type="time"
                        value={shiftData.end_time}
                        onChange={e => setShiftData({ ...shiftData, end_time: e.target.value })}
                        fullWidth
                        margin="normal"
                    />
                    <FormControl fullWidth margin="normal">
                        <InputLabel>{getWord('timezone')}</InputLabel>
                        <Select
                            value={shiftData.tz || currentTimezone}
                            onChange={e => setShiftData({ ...shiftData, tz: e.target.value })}
                            label={getWord('timezone')}
                            open={timezoneOpen}
                            onOpen={() => setTimezoneOpen(true)}
                            onClose={() => setTimezoneOpen(false)}
                            MenuProps={{
                                PaperProps: {
                                    style: {
                                        maxHeight: 300
                                    }
                                },
                                disableScrollLock: true
                            }}
                        >
                            {timezones.map((timezone) => (
                                <MenuItem key={timezone} value={timezone}>
                                    {timezone}
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    <FormControl fullWidth margin="normal">
                        <InputLabel>{getWord('type')}</InputLabel>
                        <Select
                            value={shiftData.type}
                            onChange={e => setShiftData({ ...shiftData, type: e.target.value })}
                            label={getWord('type')}
                        >
                            <MenuItem value="PHONE">{getWord('phone')}</MenuItem>
                            <MenuItem value="SMS">{getWord('sms')}</MenuItem>
                            <MenuItem value="PHONE,SMS">{getWord('phone_sms')}</MenuItem>
                        </Select>
                    </FormControl>
                    <Button variant="contained" color="primary" onClick={saveShift}>{getWord('save_shift')}</Button>
                    <Button variant="outlined" onClick={() => {
                        setShowModal(false);
                        setTimezoneOpen(false);
                    }} style={{ marginLeft: '10px' }}>{getWord('close')}</Button>
                </Box>
            </Modal>
        </div>
    );
}

export default Volunteers;
