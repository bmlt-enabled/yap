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
    Checkbox,
    Typography,
} from '@mui/material';
import VolunteerActivismIcon from '@mui/icons-material/VolunteerActivism';
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
    const [showGroupModal, setShowGroupModal] = useState(false);
    const [groups, setGroups] = useState([]);
    const [selectedGroupId, setSelectedGroupId] = useState(0);
    const [selectedDays, setSelectedDays] = useState([]);
    const [phoneValidationCountry, setPhoneValidationCountry] = useState('US');
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

    // Fetch phone validation country setting
    React.useEffect(() => {
        const fetchPhoneValidation = async () => {
            try {
                const response = await apiClient.get('/api/v1/settings');
                const phoneValidation = response.data.settings?.find(s => s.name === 'phone_number_validation');
                if (phoneValidation?.value) {
                    setPhoneValidationCountry(phoneValidation.value);
                }
            } catch (error) {
                console.error('Error fetching phone validation setting:', error);
            }
        };
        fetchPhoneValidation();
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
            let response = await apiClient(`/api/v1/volunteers?serviceBodyId=${serviceBodyId}`);
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
            let response = await apiClient.post(`/api/v1/volunteers?serviceBodyId=${serviceBodyId}`, encodedVolunteers);
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

    const loadGroups = async () => {
        if (!serviceBodyId) return;

        try {
            const response = await apiClient.get(`/api/v1/groups?serviceBodyId=${serviceBodyId}`);
            setGroups(response.data || []);
        } catch (error) {
            console.error('Error fetching groups:', error);
        }
    };

    const handleShowGroupModal = () => {
        loadGroups();
        setShowGroupModal(true);
    };

    const handleIncludeGroup = async () => {
        if (selectedGroupId === 0) {
            return;
        }

        try {
            // Fetch group volunteers
            const response = await apiClient.get(`/api/v1/groups/volunteers?groupId=${selectedGroupId}`);
            const responseData = response.data;

            // Parse the volunteers data
            let groupVolunteers = [];
            if (responseData && responseData.data) {
                if (typeof responseData.data === 'string') {
                    groupVolunteers = JSON.parse(responseData.data);
                } else {
                    groupVolunteers = responseData.data;
                }
            }

            // Find the group info
            const group = groups.find(g => g.id === parseInt(selectedGroupId));

            // Create a group reference object to add to volunteers list
            const groupReference = {
                group_id: selectedGroupId,
                group_name: group?.data[0]?.group_name || 'Group',
                group_enabled: true,
                isGroup: true // Flag to identify this as a group reference
            };

            // Add the group reference to the volunteers list
            setVolunteers([...volunteers, groupReference]);

            // Close modal and reset selection
            setShowGroupModal(false);
            setSelectedGroupId(0);
        } catch (error) {
            console.error('Error including group:', error);
        }
    };

    return (
        <Box sx={{ p: 3 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', mb: 3 }}>
                <VolunteerActivismIcon sx={{ fontSize: 40, mr: 2 }} />
                <Typography variant="h4">
                    Volunteers
                </Typography>
            </Box>
            <div className="container">
                <div className="form-group">
                    <ServiceBodiesDropdown handleChange={serviceBodiesHandleChange}/>
                {serviceBodyId > 0 ?
                    <ButtonGroup sx={{
                        padding: 2
                    }}>
                        <Button variant="contained" color="primary" onClick={handleAddVolunteer}>{getWord('add_volunteer')}</Button>
                        <Button variant="contained" color="success" onClick={saveVolunteers}>{getWord('save_volunteers')}</Button>
                        <Button variant="contained" color="warning" onClick={handleShowGroupModal}>{getWord('include_group')}</Button>
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
                                phoneValidationCountry={phoneValidationCountry}
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
                        <InputLabel sx={{ mb: 1 }}>{getWord('start_time')}</InputLabel>
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
                        <InputLabel sx={{ mb: 1 }}>{getWord('end_time')}</InputLabel>
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

            <Modal open={showGroupModal} onClose={() => {
                setShowGroupModal(false);
                setSelectedGroupId(0);
            }}>
                <Box sx={{
                    position: 'absolute',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    width: 500,
                    bgcolor: 'background.paper',
                    border: '2px solid #000',
                    boxShadow: 24,
                    p: 4
                }}>
                    <h2>{getWord('include_group') || 'Include Group'}</h2>
                    <FormControl fullWidth margin="normal">
                        <InputLabel>{getWord('select_a_group') || 'Select a Group'}</InputLabel>
                        <Select
                            value={selectedGroupId}
                            onChange={e => setSelectedGroupId(e.target.value)}
                            label={getWord('select_a_group') || 'Select a Group'}
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
                            <MenuItem value={0}>-= {getWord('select_a_group') || 'Select a Group'} =-</MenuItem>
                            {groups.map((group) => (
                                <MenuItem key={group.id} value={group.id}>
                                    {group.data[0]?.group_name || 'Unnamed Group'} ({group.id})
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    <Box sx={{ mt: 2, display: 'flex', gap: 1 }}>
                        <Button
                            variant="contained"
                            color="primary"
                            onClick={handleIncludeGroup}
                            disabled={selectedGroupId === 0}
                        >
                            {getWord('ok') || 'OK'}
                        </Button>
                        <Button
                            variant="outlined"
                            onClick={() => {
                                setShowGroupModal(false);
                                setSelectedGroupId(0);
                            }}
                        >
                            {getWord('cancel') || 'Cancel'}
                        </Button>
                    </Box>
                </Box>
            </Modal>
            </div>
        </Box>
    );
}

export default Volunteers;
