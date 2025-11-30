import React, {useState} from "react";
import {
    useSortable,
} from '@dnd-kit/sortable';
import {CSS} from '@dnd-kit/utilities';
import {
    Box,
    Button,
    TextField,
    FormControl,
    Collapse,
    Checkbox,
    FormControlLabel,
    IconButton, FormLabel, Radio, RadioGroup
} from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';

export default function SortableVolunteer({ volunteer, index, volunteers, setVolunteers, expanded, toggleExpand, handleAddShift, handleRemoveShift, handleRemoveAllShifts, daysOfWeek, getWord }) {
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

    // Check if this is a group reference
    const isGroup = volunteer.group_id !== undefined;

    // If it's a group, render a simplified version
    if (isGroup) {
        return (
            <Box
                ref={setNodeRef}
                style={style}
                sx={{
                    border: '2px solid',
                    borderColor: 'primary.main',
                    margin: 2,
                    padding: 2,
                    borderRadius: 2,
                    position: 'relative',
                    backgroundColor: 'action.hover',
                }}
            >
                <Box
                    display="flex"
                    alignItems="center"
                    justifyContent="space-between"
                    sx={{ padding: 2 }}
                >
                    <Box display="flex" alignItems="center" gap={2}>
                        <Box
                            sx={{
                                backgroundColor: 'primary.main',
                                color: 'primary.contrastText',
                                padding: '4px 12px',
                                borderRadius: 1,
                                fontSize: '0.875rem',
                                fontWeight: 'bold',
                            }}
                        >
                            GROUP
                        </Box>
                        <FormControlLabel
                            control={
                                <Checkbox
                                    checked={!!volunteer.group_enabled}
                                    onChange={e => {
                                        const newValue = e.target.checked;
                                        setVolunteers(prevVolunteers =>
                                            prevVolunteers.map((v, i) =>
                                                i === index
                                                    ? { ...v, group_enabled: newValue }
                                                    : v
                                            )
                                        );
                                    }}
                                />
                            }
                            label={getWord('enabled')}
                        />
                        <Box sx={{ fontSize: '1.2rem', fontWeight: 'bold' }}>
                            {volunteer.group_name}
                        </Box>
                    </Box>
                    <Button
                        variant="contained"
                        color="error"
                        onClick={() => setVolunteers(volunteers.filter((_, i) => i !== index))}
                    >
                        {getWord('remove')}
                    </Button>
                </Box>
            </Box>
        );
    }

    // Regular volunteer rendering
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
                        label={getWord('enabled')}
                    />
                </Box>
                <TextField
                    label={getWord('volunteer_name')}
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
                    {getWord('remove')}
                </Button>
            </Box>

            <Collapse in={expanded[index]}>
                <Box sx={{
                    borderTop: '1px solid #aaa',
                    position: 'relative',
                    zIndex: 2,
                }}>
                    <TextField
                        label={getWord('phone_number')}
                        value={volunteer.volunteer_phone_number}
                        onChange={e => {
                            const updatedVolunteers = [...volunteers];
                            updatedVolunteers[index].volunteer_phone_number = e.target.value;
                            setVolunteers(updatedVolunteers);
                        }}
                        margin="normal"
                    />
                    <FormControl sx={{padding: 2}}>
                        <FormLabel id="gender-group-label">{getWord('gender')}</FormLabel>
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
                            <FormControlLabel value={0} control={<Radio />} label={getWord('unassigned')} labelPlacement="bottom"/>
                            <FormControlLabel value={1} control={<Radio />} label={getWord('male')} labelPlacement="bottom" />
                            <FormControlLabel value={2} control={<Radio />} label={getWord('female')} labelPlacement="bottom" />
                            <FormControlLabel value={3} control={<Radio />} label={getWord('unspecified')} labelPlacement="bottom" />
                        </RadioGroup>
                    </FormControl>
                    <FormControl sx={{padding: 2}}>
                        <FormLabel id="options-group-label">{getWord('options')}</FormLabel>
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
                        label={getWord('responder')}
                        />
                    </FormControl>
                </Box>
                <Box sx={{ position: 'relative', zIndex: 2, display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                    <Button variant="outlined" onClick={() => handleAddShift(index)}>
                        {getWord('add_shift') || 'Add Shift'}
                    </Button>
                    <Button variant="outlined" color="error" onClick={() => handleRemoveAllShifts(index)}>
                        {getWord('remove_all_shifts') || 'Remove All Shifts'}
                    </Button>
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
                                    {shift.day_name}
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
                                    {shift.tz && (
                                        <Box sx={{
                                            backgroundColor: '#6c757d',
                                            color: 'white',
                                            padding: '2px 6px',
                                            borderRadius: 1,
                                            fontSize: '0.7rem',
                                            fontWeight: 'bold',
                                            marginLeft: 1
                                        }}>
                                            {shift.tz}
                                        </Box>
                                    )}
                                </Box>
                            </Box>
                            <Box sx={{
                                display: 'flex',
                                gap: 0.5
                            }}>
                                {shift.type?.includes('PHONE') && (
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
                                {shift.type?.includes('SMS') && (
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
                            {getWord('remove_shift')}
                        </Button>
                    </Box>
                ))}

                <TextField
                    label={getWord('notes')}
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