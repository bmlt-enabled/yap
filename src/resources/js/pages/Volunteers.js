import React, {useState} from "react";
import ServiceBodiesDropdown from "../components/ServiceBodiesDropdown";
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
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
    IconButton
} from '@mui/material';
import apiClient from "../services/api";
import {defaultVolunteer} from "../models/VolunteerModel";
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';

function Volunteers() {
    const [volunteers, setVolunteers] = useState([]);
    const [serviceBodyId, setServiceBodyId] = useState();
    const [showModal, setShowModal] = useState(false);
    const [shiftData, setShiftData] = useState({ day: '', start_time: '', end_time: '', type: 'PHONE' });
    const [expanded, setExpanded] = useState({});


    const serviceBodiesHandleChange = (event, index) => {
        setServiceBodyId(event)
        getVolunteers(event)
    }

    const handleAddVolunteer = () => {
        setVolunteers([...volunteers, {...defaultVolunteer}]);
    };

    const getVolunteers = async (serviceBodyId) => {
        // setLoading(true)
        let response = await apiClient(`${rootUrl}/api/v1/volunteers?serviceBodyId=${serviceBodyId}`)
        let responseData = await response.data
        setVolunteers(responseData.data)
        //setLoading(false)
    }

    const saveVolunteers = async (event) => {
        let response = await apiClient.post(`${rootUrl}/api/v1/volunteers?serviceBodyId=${serviceBodyId}`, volunteers)
        let responseData = await response.data
        setVolunteers(responseData.data)
    }

    const handleAddShift = (volunteerIndex) => {
        setCurrentVolunteer(volunteerIndex);
        setShowModal(true);
    };

    const saveShift = () => {
        const updatedVolunteers = [...volunteers];
        updatedVolunteers[currentVolunteer].shifts.push(shiftData);
        setVolunteers(updatedVolunteers);
        setShowModal(false);
        setShiftData({ day: '', startTime: '', endTime: '', type: 'PHONE' });
    };

    const handleRemoveShift = (volunteerIndex, shiftIndex) => {
        const updatedVolunteers = [...volunteers];
        updatedVolunteers[volunteerIndex].shifts.splice(shiftIndex, 1);
        setVolunteers(updatedVolunteers);
    };

    const handleRemoveAllShifts = (volunteerIndex) => {
        const updatedVolunteers = [...volunteers];
        updatedVolunteers[volunteerIndex].shifts = [];
        setVolunteers(updatedVolunteers);
    };

    const handleDragEnd = (result) => {
        if (!result.destination) return;
        const reorderedVolunteers = Array.from(volunteers);
        const [movedVolunteer] = reorderedVolunteers.splice(result.source.index, 1);
        reorderedVolunteers.splice(result.destination.index, 0, movedVolunteer);
        setVolunteers(reorderedVolunteers);
    };

    const toggleExpand = (id) => {
        setExpanded(prev => ({ ...prev, [id]: !prev[id] }));
    };

    return (
        <div className="container">
            <div className="form-group">
                <ServiceBodiesDropdown handleChange={serviceBodiesHandleChange}/>
                {serviceBodyId > 0 ?
                    <ButtonGroup>
                        <Button variant="contained" color="primary" onClick={handleAddVolunteer}>Add Volunteer</Button>
                        <Button variant="contained" color="success" onClick={saveVolunteers}>Save Volunteers</Button>
                        <Button>Include Group</Button>
                    </ButtonGroup> : ""}
            </div>

            <DragDropContext onDragEnd={handleDragEnd}>
                <Droppable droppableId="volunteers">
                    {(provided) => (
                        <div {...provided.droppableProps} ref={provided.innerRef} className="volunteer-list">
                            {volunteers && volunteers.length > 0 && volunteers.map((volunteer, index) => (
                                <Draggable key={index} draggableId={index.toString()} index={index}>
                                    {(provided) => (
                                        <Box
                                            ref={provided.innerRef} {...provided.draggableProps} {...provided.dragHandleProps}
                                            sx={{
                                                border: '1px solid #ccc',
                                                padding: 2,
                                                margin: 2,
                                                borderRadius: 2,
                                                // backgroundColor: '#ccc'
                                            }}>
                                            <Box display="flex" alignItems="center" justifyContent="space-between">
                                                <IconButton onClick={() => toggleExpand(index)}>
                                                    {expanded[index] ? <ExpandLessIcon/> : <ExpandMoreIcon/>}
                                                </IconButton>
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
                                                />
                                                <FormControlLabel
                                                    control={<Checkbox checked={volunteer.volunteer_gender} onChange={e => {
                                                        const updatedVolunteers = [...volunteers];
                                                        updatedVolunteers[index].volunteer_gender = e.target.checked;
                                                        setVolunteers(updatedVolunteers);
                                                    }}/>}
                                                    label="Enabled"
                                                />
                                                <Button variant="contained" color="error"
                                                        onClick={() => setVolunteers(volunteers.filter((_, i) => i !== index))}>Remove</Button>
                                            </Box>

                                            <Collapse in={expanded[index]}>
                                                <TextField
                                                    label="Phone Number"
                                                    value={volunteer.volunteer_phone_number}
                                                    onChange={e => {
                                                        const updatedVolunteers = [...volunteers];
                                                        updatedVolunteers[index].volunteer_phone_number = e.target.value;
                                                        setVolunteers(updatedVolunteers);
                                                    }}
                                                    fullWidth
                                                    margin="normal"
                                                />
                                                <Button variant="outlined" onClick={() => handleAddShift(index)}>Add
                                                    Shift</Button>
                                                <Button variant="outlined" color="error"
                                                        onClick={() => handleRemoveAllShifts(index)}
                                                        style={{marginLeft: '10px'}}>Remove All Shifts</Button>

                                                {volunteer.volunteer_shift_schedule.length > 0 && volunteer.volunteer_shift_schedule.map((shift, shiftIndex) => (
                                                    <Box key={shiftIndex} sx={{
                                                        backgroundColor: '#f0f0f0',
                                                        padding: 1,
                                                        marginTop: 1,
                                                        borderRadius: 1
                                                    }}>
                                                        <p>Day: {shift.day}, Start: {shift.start_time},
                                                            End: {shift.end_time}, Type: {shift.type}</p>
                                                        <Button variant="contained" color="error"
                                                                onClick={() => handleRemoveShift(index, shiftIndex)}>Remove
                                                            Shift</Button>
                                                    </Box>
                                                ))}

                                                <TextField
                                                    label="Notes"
                                                    multiline
                                                    rows={3}
                                                    fullWidth
                                                    margin="normal"
                                                />
                                            </Collapse>
                                        </Box>
                                    )}
                                </Draggable>
                            ))}
                            {provided.placeholder}
                        </div>
                    )}
                </Droppable>
            </DragDropContext>

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
                    <TextField
                        label="Day"
                        value={shiftData.day}
                        onChange={e => setShiftData({...shiftData, day: e.target.value})}
                        fullWidth
                        margin="normal"
                    />
                    <TextField
                        label="Start Time"
                        type="time"
                        value={shiftData.start_time}
                        onChange={e => setShiftData({...shiftData, start_time: e.target.value})}
                        fullWidth
                        margin="normal"
                    />
                    <TextField
                        label="End Time"
                        type="time"
                        value={shiftData.end_time}
                        onChange={e => setShiftData({...shiftData, end_time: e.target.value})}
                        fullWidth
                        margin="normal"
                    />
                    <FormControl fullWidth margin="normal">
                        <InputLabel>Type</InputLabel>
                        <Select
                            value={shiftData.type}
                            onChange={e => setShiftData({...shiftData, type: e.target.value})}
                            label="Type"
                        >
                            <MenuItem value="PHONE">Phone</MenuItem>
                            <MenuItem value="SMS">SMS</MenuItem>
                            <MenuItem value="PHONE,SMS">Phone + SMS</MenuItem>
                        </Select>
                    </FormControl>
                    <Button variant="contained" color="primary" onClick={saveShift}>Save Shift</Button>
                    <Button variant="outlined" onClick={() => setShowModal(false)}
                            style={{marginLeft: '10px'}}>Close</Button>
                </Box>
            </Modal>
        </div>
    );
}

export default Volunteers;
