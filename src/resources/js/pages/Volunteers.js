import React, {useState} from "react";
import ServiceBodiesDropdown from "../components/ServiceBodiesDropdown";
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
    useSortable,
} from '@dnd-kit/sortable';
import {CSS} from '@dnd-kit/utilities';
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
    Collapse,
    Checkbox,
    FormControlLabel,
    IconButton, FormLabel, Radio, RadioGroup, CircularProgress
} from '@mui/material';
import apiClient from "../services/api";
import {defaultVolunteer} from "../models/VolunteerModel";
import {defaultShift} from "../models/ShiftModel";
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';

// Sortable Volunteer Component
function SortableVolunteer({ volunteer, index, volunteers, setVolunteers, expanded, toggleExpand, handleAddShift, handleRemoveShift, handleRemoveAllShifts, daysOfWeek }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: index.toString() });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <Box
            ref={setNodeRef}
            style={style}
            sx={{
                border: '1px solid #ccc',
                margin: 2,
                padding: 2,
                borderRadius: 2,
                position: 'relative',
            }}
        >
            <Box
                display="flex"
                alignItems="center"
                justifyContent="space-between"
                sx={{
                    padding: 2
                }}
            >
                <Box display="flex" alignItems="center" sx={{ position: 'relative', zIndex: 2 }}>
                    <IconButton 
                        onClick={(e) => {
                            e.stopPropagation();
                            toggleExpand(index);
                        }}
                    >
                        {expanded[index] ? <ExpandLessIcon/> : <ExpandMoreIcon/>}
                    </IconButton>
                    <FormControlLabel
                        control={
                            <Checkbox
                                checked={!!volunteer.volunteer_enabled}
                                onChange={e => {
                                    const newValue = e.target.checked;
                                    setVolunteers(prevVolunteers => 
                                        prevVolunteers.map((v, i) => 
                                            i === index 
                                                ? { ...v, volunteer_enabled: newValue }
                                                : v
                                        )
                                    );
                                }}
                            />
                        }
                        label="Enabled"
                    />
                </Box>
                <TextField
                    label="Volunteer Name"
                    value={volunteer.volunteer_name}
                    onChange={e => {
                        const updatedVolunteers = [...volunteers];
                        updatedVolunteers[index].volunteer_name = e.target.value;
                        setVolunteers(updatedVolunteers);
                    }}
                    size="small"
                    margin="normal"
                    sx={{ position: 'relative', zIndex: 2 }}
                />
                <Button 
                    variant="contained" 
                    color="error"
                    onClick={() => setVolunteers(volunteers.filter((_, i) => i !== index))}
                    sx={{ position: 'relative', zIndex: 2 }}
                >
                    Remove
                </Button>
            </Box>

            <Collapse in={expanded[index]}>
                <Box sx={{
                    borderTop: '1px solid #aaa',
                    position: 'relative',
                    zIndex: 2,
                }}>
                    <TextField
                        label="Phone Number"
                        value={volunteer.volunteer_phone_number}
                        onChange={e => {
                            const updatedVolunteers = [...volunteers];
                            updatedVolunteers[index].volunteer_phone_number = e.target.value;
                            setVolunteers(updatedVolunteers);
                        }}
                        margin="normal"
                    />
                    <FormControl sx={{padding: 2}}>
                        <FormLabel id="gender-group-label">Gender</FormLabel>
                        <RadioGroup
                            row
                            aria-labelledby="demo-radio-buttons-group-label"
                            value={parseInt(volunteer.volunteer_gender, 10)}
                            name="radio-buttons-group"
                            onChange={e => {
                                const updatedVolunteers = [...volunteers];
                                updatedVolunteers[index].volunteer_gender = parseInt(e.target.value, 10);
                                setVolunteers(updatedVolunteers);
                            }}
                        >
                            <FormControlLabel value={0} control={<Radio />} label="Unassigned" labelPlacement="bottom"/>
                            <FormControlLabel value={1} control={<Radio />} label="Male" labelPlacement="bottom" />
                            <FormControlLabel value={2} control={<Radio />} label="Female" labelPlacement="bottom" />
                            <FormControlLabel value={3} control={<Radio />} label="Unspecified" labelPlacement="bottom" />
                        </RadioGroup>
                    </FormControl>
                    <FormControl sx={{padding: 2}}>
                        <FormLabel id="options-group-label">Options</FormLabel>
                        <FormControlLabel control={
                            <Checkbox
                                checked={volunteer.volunteer_responder === 1}
                                onChange={e => {
                                    const updatedVolunteers = [...volunteers];
                                    updatedVolunteers[index].volunteer_responder = e.target.checked ? 1 : 0;
                                    setVolunteers(updatedVolunteers);
                                }}
                            />
                        }
                        label="Responder"
                        />
                    </FormControl>
                </Box>
                <Box sx={{ position: 'relative', zIndex: 2 }}>
                    <Button variant="outlined" onClick={() => handleAddShift(index)}>Add
                        Shift</Button>
                    <Button variant="outlined" color="error"
                            onClick={() => handleRemoveAllShifts(index)}
                            style={{marginLeft: '10px'}}>Remove All Shifts</Button>
                </Box>

                {Array.isArray(volunteer.volunteer_shift_schedule) && volunteer.volunteer_shift_schedule.length > 0 && volunteer.volunteer_shift_schedule.map((shift, shiftIndex) => (
                    <Box key={shiftIndex} sx={{
                        backgroundColor: 'transparent',
                        border: '1px solid #e9ecef',
                        padding: 2,
                        marginTop: 2,
                        borderRadius: 2,
                        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
                        position: 'relative',
                        zIndex: 2,
                    }}>
                        <Box sx={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            marginBottom: 1
                        }}>
                            <Box sx={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 1
                            }}>
                                <Box sx={{
                                    backgroundColor: '#007bff',
                                    color: 'white',
                                    padding: '4px 8px',
                                    borderRadius: 1,
                                    fontSize: '0.875rem',
                                    fontWeight: 'bold',
                                    minWidth: '80px',
                                    textAlign: 'center'
                                }}>
                                    {daysOfWeek[shift.day]}
                                </Box>
                                <Box sx={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 1,
                                    color: '#495057'
                                }}>
                                    <span style={{ fontWeight: 'bold' }}>{shift.start_time}</span>
                                    <span>→</span>
                                    <span style={{ fontWeight: 'bold' }}>{shift.end_time}</span>
                                </Box>
                            </Box>
                            <Box sx={{
                                display: 'flex',
                                gap: 0.5
                            }}>
                                {shift.type.includes('PHONE') && (
                                    <Box sx={{
                                        backgroundColor: '#28a745',
                                        color: 'white',
                                        padding: '2px 6px',
                                        borderRadius: 1,
                                        fontSize: '0.75rem',
                                        fontWeight: 'bold'
                                    }}>
                                        📞 Phone
                                    </Box>
                                )}
                                {shift.type.includes('SMS') && (
                                    <Box sx={{
                                        backgroundColor: '#17a2b8',
                                        color: 'white',
                                        padding: '2px 6px',
                                        borderRadius: 1,
                                        fontSize: '0.75rem',
                                        fontWeight: 'bold'
                                    }}>
                                        💬 SMS
                                    </Box>
                                )}
                            </Box>
                        </Box>
                        <Button 
                            variant="outlined" 
                            color="error" 
                            size="small"
                            onClick={() => handleRemoveShift(index, shiftIndex)}
                            sx={{ marginTop: 1 }}
                        >
                            Remove Shift
                        </Button>
                    </Box>
                ))}

                <TextField
                    label="Notes"
                    multiline
                    rows={3}
                    fullWidth
                    value={volunteer.volunteer_notes}
                    onChange={e => {
                        const updatedVolunteers = [...volunteers];
                        updatedVolunteers[index].volunteer_notes = e.target.value;
                        setVolunteers(updatedVolunteers);
                    }}
                    margin="normal"
                    sx={{ position: 'relative', zIndex: 2 }}
                />
            </Collapse>

            {/* Drag handle - covers the entire area except for interactive elements */}
            <Box
                {...attributes}
                {...listeners}
                sx={{
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    cursor: 'grab',
                    '&:active': {
                        cursor: 'grabbing',
                    },
                    zIndex: 1,
                }}
            />
        </Box>
    );
}

function Volunteers() {
    const [volunteers, setVolunteers] = useState([]);
    const [serviceBodyId, setServiceBodyId] = useState();
    const [showModal, setShowModal] = useState(false);
    const [currentVolunteer, setCurrentVolunteer] = useState();
    const [shiftData, setShiftData] = useState("");
    const [expanded, setExpanded] = useState({});
    const [loading, setLoading] = useState(false);
    const daysOfWeek = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday"
    ];

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const decodeShiftSchedule = (base64Schedule) => {
        try {
            if (!base64Schedule) return [];
            const decodedString = atob(base64Schedule);
            return JSON.parse(decodedString);
        } catch (error) {
            console.error('Error decoding shift schedule:', error);
            return [];
        }
    };

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
        setShiftData(defaultShift);
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
                        <Button variant="contained" color="primary" onClick={handleAddVolunteer}>Add Volunteer</Button>
                        <Button variant="contained" color="success" onClick={saveVolunteers}>Save Volunteers</Button>
                        <Button variant="contained" color="warning">Include Group</Button>
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
                            />
                        ))}
                    </SortableContext>
                </DndContext>
            )}

            <Modal open={showModal} onClose={() => setShowModal(false)}>
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
                    <h2>Add Shift</h2>
                    <FormControl fullWidth margin="normal">
                        <InputLabel>Day of the Week</InputLabel>
                        <Select
                            value={shiftData.day}
                            onChange={e => setShiftData({ ...shiftData, day: e.target.value })}
                            label="Day of the Week"
                        >
                            {daysOfWeek.map((day, index) => (
                                <MenuItem key={index} value={index}>{day}</MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                    <TextField
                        label="Start Time"
                        type="time"
                        value={shiftData.start_time}
                        onChange={e => setShiftData({ ...shiftData, start_time: e.target.value })}
                        fullWidth
                        margin="normal"
                    />
                    <TextField
                        label="End Time"
                        type="time"
                        value={shiftData.end_time}
                        onChange={e => setShiftData({ ...shiftData, end_time: e.target.value })}
                        fullWidth
                        margin="normal"
                    />
                    <FormControl fullWidth margin="normal">
                        <InputLabel>Type</InputLabel>
                        <Select
                            value={shiftData.type}
                            onChange={e => setShiftData({ ...shiftData, type: e.target.value })}
                            label="Type"
                        >
                            <MenuItem value="PHONE">Phone</MenuItem>
                            <MenuItem value="SMS">SMS</MenuItem>
                            <MenuItem value="PHONE,SMS">Phone + SMS</MenuItem>
                        </Select>
                    </FormControl>
                    <Button variant="contained" color="primary" onClick={saveShift}>Save Shift</Button>
                    <Button variant="outlined" onClick={() => setShowModal(false)} style={{ marginLeft: '10px' }}>Close</Button>
                </Box>
            </Modal>
        </div>
    );
}

export default Volunteers;
