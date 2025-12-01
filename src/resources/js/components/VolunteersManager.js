import { useState, useEffect } from "react";
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
    Checkbox,
} from '@mui/material';
import apiClient from "../services/api";
import { defaultVolunteer } from "../models/VolunteerModel";
import { defaultShift } from "../models/ShiftModel";
import SortableVolunteer from "../components/SortableVolunteer";

/**
 * VolunteersManager - Reusable component for managing volunteers
 * Can be used for both service body volunteers and group volunteers
 *
 * @param {Object} props
 * @param {number} props.serviceBodyId - The service body ID
 * @param {number} props.groupId - Optional group ID (if managing group volunteers)
 * @param {string} props.dataType - Optional data type identifier
 */
function VolunteersManager({ serviceBodyId, groupId = null }) {
    const { getWord } = useLocalization();
    const [volunteers, setVolunteers] = useState([]);
    const [showModal, setShowModal] = useState(false);
    const [currentVolunteer, setCurrentVolunteer] = useState();
    const [shiftData, setShiftData] = useState("");
    const [expanded, setExpanded] = useState({});
    const [loading, setLoading] = useState(false);
    const [timezones, setTimezones] = useState([]);
    const [currentTimezone, setCurrentTimezone] = useState('');
    const [timezoneOpen, setTimezoneOpen] = useState(false);
    const [selectedDays, setSelectedDays] = useState([]);
    const daysOfWeek = getWord('days_of_the_week');

    // Get current browser timezone
    useEffect(() => {
        const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        setCurrentTimezone(browserTimezone);
        setShiftData(prev => ({ ...prev, tz: browserTimezone }));
    }, []);

    // Fetch timezones from API
    useEffect(() => {
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

    // Load volunteers when serviceBodyId or groupId changes
    useEffect(() => {
        if (serviceBodyId || groupId) {
            getVolunteers();
        }
    }, [serviceBodyId, groupId]);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const handleAddVolunteer = () => {
        setVolunteers([...volunteers, { ...defaultVolunteer }]);
    };

    const getVolunteers = async () => {
        setLoading(true);
        try {
            let response;
            if (groupId) {
                // Fetch group volunteers
                response = await apiClient.get(`/api/v1/groups/volunteers?groupId=${groupId}`);
                const responseData = response.data;

                // Parse the data field if it exists and is a string
                if (responseData && responseData.data) {
                    let parsedData;
                    if (typeof responseData.data === 'string') {
                        parsedData = JSON.parse(responseData.data);
                    } else {
                        parsedData = responseData.data;
                    }
                    setVolunteers(parsedData || []);
                } else {
                    setVolunteers([]);
                }
            } else if (serviceBodyId) {
                // Fetch service body volunteers
                response = await apiClient.get(`/api/v1/volunteers?serviceBodyId=${serviceBodyId}`);
                const responseData = response.data;
                setVolunteers(responseData.data || []);
            }
        } catch (error) {
            console.error('Error fetching volunteers:', error);
            setVolunteers([]);
        } finally {
            setLoading(false);
        }
    };

    const saveVolunteers = async () => {
        setLoading(true);
        try {
            // Encode shift schedules before saving
            const encodedVolunteers = volunteers.map(volunteer => ({
                ...volunteer,
                volunteer_shift_schedule: btoa(JSON.stringify(volunteer.volunteer_shift_schedule))
            }));

            let response;
            if (groupId) {
                // Save group volunteers
                response = await apiClient.post(
                    `/api/v1/groups/volunteers?groupId=${groupId}&serviceBodyId=${serviceBodyId}`,
                    encodedVolunteers
                );
            } else {
                // Save service body volunteers
                response = await apiClient.post(
                    `/api/v1/volunteers?serviceBodyId=${serviceBodyId}`,
                    encodedVolunteers
                );
            }

            const responseData = response.data;

            // Handle different response formats
            if (groupId && responseData && responseData.data) {
                let parsedData;
                if (typeof responseData.data === 'string') {
                    parsedData = JSON.parse(responseData.data);
                } else {
                    parsedData = responseData.data;
                }
                setVolunteers(parsedData || []);
            } else if (responseData.data) {
                setVolunteers(responseData.data || []);
            }
        } catch (error) {
            console.error('Error saving volunteers:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleAddShift = (volunteerIndex) => {
        setCurrentVolunteer(volunteerIndex);
        setShiftData({ ...defaultShift, tz: currentTimezone });
        setSelectedDays([]);
        setShowModal(true);
    };

    const saveShift = () => {
        if (selectedDays.length === 0) {
            return; // Don't save if no days selected
        }

        const updatedVolunteers = [...volunteers];

        if (!Array.isArray(updatedVolunteers[currentVolunteer].volunteer_shift_schedule)) {
            updatedVolunteers[currentVolunteer].volunteer_shift_schedule = [];
        }

        // Add shift for each selected day
        selectedDays.forEach(day => {
            updatedVolunteers[currentVolunteer].volunteer_shift_schedule.push({
                ...shiftData,
                day: day.toString(),
                day_name: daysOfWeek[day]
            });
        });

        setVolunteers(updatedVolunteers);
        setShowModal(false);
        setShiftData({ ...defaultShift, tz: currentTimezone });
        setTimezoneOpen(false);
        setSelectedDays([]);
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
        <Box>
            <ButtonGroup sx={{ mb: 2 }}>
                <Button variant="contained" color="primary" onClick={handleAddVolunteer}>
                    {getWord('add_volunteer') || 'Add Volunteer'}
                </Button>
                <Button variant="contained" color="success" onClick={saveVolunteers} disabled={loading}>
                    {getWord('save_volunteers') || 'Save Volunteers'}
                </Button>
            </ButtonGroup>

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
                    <h2>{getWord('add_shift') || 'Add Shift'}</h2>

                    <FormControl fullWidth margin="normal">
                        <InputLabel>{getWord('select_days') || 'Select Days'}</InputLabel>
                        <Select
                            multiple
                            value={selectedDays}
                            onChange={e => setSelectedDays(e.target.value)}
                            label={getWord('select_days') || 'Select Days'}
                            renderValue={(selected) => selected.map(day => daysOfWeek[day]).join(', ')}
                            MenuProps={{
                                disablePortal: false,
                                keepMounted: true,
                                PaperProps: {
                                    style: {
                                        maxHeight: 400,
                                    },
                                },
                            }}
                        >
                            {daysOfWeek && Object.keys(daysOfWeek).map((key) => (
                                <MenuItem key={key} value={key}>
                                    <Checkbox checked={selectedDays.indexOf(key) > -1} />
                                    {daysOfWeek[key]}
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    <Box>
                        <InputLabel sx={{ mb: 1 }}>{getWord('start_time') || 'Start Time'}</InputLabel>
                        <Box sx={{ display: 'flex', gap: 1 }}>
                            <FormControl sx={{ minWidth: 80 }}>
                                <Select
                                    value={shiftData.start_time?.split(':')[0] || '12'}
                                    onChange={e => {
                                        const hour = e.target.value;
                                        const minute = shiftData.start_time?.split(':')[1]?.split(' ')[0] || '00';
                                        const period = shiftData.start_time?.split(' ')[1] || 'AM';
                                        setShiftData({ ...shiftData, start_time: `${hour}:${minute} ${period}` });
                                    }}
                                    size="small"
                                >
                                    {[...Array(12)].map((_, i) => {
                                        const hour = String(i + 1).padStart(2, '0');
                                        return <MenuItem key={hour} value={hour}>{hour}</MenuItem>;
                                    })}
                                </Select>
                            </FormControl>
                            <FormControl sx={{ minWidth: 80 }}>
                                <Select
                                    value={shiftData.start_time?.split(':')[1]?.split(' ')[0] || '00'}
                                    onChange={e => {
                                        const hour = shiftData.start_time?.split(':')[0] || '12';
                                        const minute = e.target.value;
                                        const period = shiftData.start_time?.split(' ')[1] || 'AM';
                                        setShiftData({ ...shiftData, start_time: `${hour}:${minute} ${period}` });
                                    }}
                                    size="small"
                                >
                                    {[...Array(60)].map((_, i) => {
                                        const minute = String(i).padStart(2, '0');
                                        return <MenuItem key={minute} value={minute}>{minute}</MenuItem>;
                                    })}
                                </Select>
                            </FormControl>
                            <FormControl sx={{ minWidth: 80 }}>
                                <Select
                                    value={shiftData.start_time?.split(' ')[1] || 'AM'}
                                    onChange={e => {
                                        const hour = shiftData.start_time?.split(':')[0] || '12';
                                        const minute = shiftData.start_time?.split(':')[1]?.split(' ')[0] || '00';
                                        const period = e.target.value;
                                        setShiftData({ ...shiftData, start_time: `${hour}:${minute} ${period}` });
                                    }}
                                    size="small"
                                >
                                    <MenuItem value="AM">AM</MenuItem>
                                    <MenuItem value="PM">PM</MenuItem>
                                </Select>
                            </FormControl>
                        </Box>
                    </Box>
                    <Box sx={{ mt: 2 }}>
                        <InputLabel sx={{ mb: 1 }}>{getWord('end_time') || 'End Time'}</InputLabel>
                        <Box sx={{ display: 'flex', gap: 1 }}>
                            <FormControl sx={{ minWidth: 80 }}>
                                <Select
                                    value={shiftData.end_time?.split(':')[0] || '12'}
                                    onChange={e => {
                                        const hour = e.target.value;
                                        const minute = shiftData.end_time?.split(':')[1]?.split(' ')[0] || '00';
                                        const period = shiftData.end_time?.split(' ')[1] || 'AM';
                                        setShiftData({ ...shiftData, end_time: `${hour}:${minute} ${period}` });
                                    }}
                                    size="small"
                                >
                                    {[...Array(12)].map((_, i) => {
                                        const hour = String(i + 1).padStart(2, '0');
                                        return <MenuItem key={hour} value={hour}>{hour}</MenuItem>;
                                    })}
                                </Select>
                            </FormControl>
                            <FormControl sx={{ minWidth: 80 }}>
                                <Select
                                    value={shiftData.end_time?.split(':')[1]?.split(' ')[0] || '00'}
                                    onChange={e => {
                                        const hour = shiftData.end_time?.split(':')[0] || '12';
                                        const minute = e.target.value;
                                        const period = shiftData.end_time?.split(' ')[1] || 'AM';
                                        setShiftData({ ...shiftData, end_time: `${hour}:${minute} ${period}` });
                                    }}
                                    size="small"
                                >
                                    {[...Array(60)].map((_, i) => {
                                        const minute = String(i).padStart(2, '0');
                                        return <MenuItem key={minute} value={minute}>{minute}</MenuItem>;
                                    })}
                                </Select>
                            </FormControl>
                            <FormControl sx={{ minWidth: 80 }}>
                                <Select
                                    value={shiftData.end_time?.split(' ')[1] || 'AM'}
                                    onChange={e => {
                                        const hour = shiftData.end_time?.split(':')[0] || '12';
                                        const minute = shiftData.end_time?.split(':')[1]?.split(' ')[0] || '00';
                                        const period = e.target.value;
                                        setShiftData({ ...shiftData, end_time: `${hour}:${minute} ${period}` });
                                    }}
                                    size="small"
                                >
                                    <MenuItem value="AM">AM</MenuItem>
                                    <MenuItem value="PM">PM</MenuItem>
                                </Select>
                            </FormControl>
                        </Box>
                    </Box>
                    <FormControl fullWidth margin="normal">
                        <InputLabel>{getWord('timezone') || 'Timezone'}</InputLabel>
                        <Select
                            value={shiftData.tz || currentTimezone}
                            onChange={e => setShiftData({ ...shiftData, tz: e.target.value })}
                            label={getWord('timezone') || 'Timezone'}
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
                        <InputLabel>{getWord('type') || 'Type'}</InputLabel>
                        <Select
                            value={shiftData.type}
                            onChange={e => setShiftData({ ...shiftData, type: e.target.value })}
                            label={getWord('type') || 'Type'}
                            MenuProps={{
                                disablePortal: false,
                                keepMounted: true,
                                PaperProps: {
                                    style: {
                                        maxHeight: 400,
                                    },
                                },
                            }}
                        >
                            <MenuItem value="PHONE">{getWord('phone') || 'Phone'}</MenuItem>
                            <MenuItem value="SMS">{getWord('sms') || 'SMS'}</MenuItem>
                            <MenuItem value="PHONE,SMS">{getWord('phone_sms') || 'Phone & SMS'}</MenuItem>
                        </Select>
                    </FormControl>
                    <Button variant="contained" color="primary" onClick={saveShift}>
                        {getWord('save_shift') || 'Save Shift'}
                    </Button>
                    <Button variant="outlined" onClick={() => {
                        setShowModal(false);
                        setTimezoneOpen(false);
                    }} style={{ marginLeft: '10px' }}>
                        {getWord('close') || 'Close'}
                    </Button>
                </Box>
            </Modal>
        </Box>
    );
}

export default VolunteersManager;
