import React, { useEffect, useState } from 'react';
import {
    Button,
    Checkbox,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    Alert,
    IconButton,
    Tooltip,
    Typography
} from '@mui/material';
import PlayArrowIcon from '@mui/icons-material/PlayArrow';
import DeleteIcon from '@mui/icons-material/Delete';
import apiClient from '../services/api';

export function VoicemailDialog({ open, onClose, serviceBodyId, serviceBodyName }) {
    const [voicemails, setVoicemails] = useState([]);
    const [selected, setSelected] = useState([]);
    const [loading, setLoading] = useState(true);
    const [alert, setAlert] = useState({ show: false, message: '', severity: 'success' });

    const fetchVoicemails = async () => {
        try {
            const response = await apiClient.get(`/api/v1/voicemail?serviceBodyId=${serviceBodyId}`);
            setVoicemails(response.data.data);
            setSelected([]);
        } catch (error) {
            console.error('Error fetching voicemails:', error);
            setAlert({
                show: true,
                message: 'Error loading voicemails',
                severity: 'error'
            });
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (callsid) => {
        if (!callsid) {
            return;
        }
        if (!window.confirm('Permanently delete this voicemail recording from Twilio? This cannot be undone.')) {
            return;
        }
        try {
            await apiClient.delete(`/api/v1/voicemail/${callsid}?serviceBodyId=${serviceBodyId}`);
            setAlert({
                show: true,
                message: 'Voicemail recording permanently deleted from Twilio',
                severity: 'success'
            });
            fetchVoicemails();
        } catch (error) {
            console.error('Error deleting voicemail:', error);
            setAlert({
                show: true,
                message: error.response?.data?.message || 'Error deleting voicemail',
                severity: 'error'
            });
        }
    };

    const handleDeleteSelected = async () => {
        if (selected.length === 0) {
            return;
        }
        const noun = selected.length === 1 ? 'recording' : 'recordings';
        if (!window.confirm(`Permanently delete ${selected.length} voicemail ${noun} from Twilio? This cannot be undone.`)) {
            return;
        }
        try {
            const response = await apiClient.post(`/api/v1/voicemail/delete?serviceBodyId=${serviceBodyId}`, {
                callsids: selected
            });
            const failed = response.data.failed || [];
            const deleted = response.data.deleted || [];
            if (failed.length === 0) {
                setAlert({
                    show: true,
                    message: 'Voicemail recordings permanently deleted from Twilio',
                    severity: 'success'
                });
            } else {
                setAlert({
                    show: true,
                    message: `Deleted ${deleted.length} voicemail(s). ${failed.length} could not be deleted.`,
                    severity: 'error'
                });
            }
            fetchVoicemails();
        } catch (error) {
            console.error('Error deleting voicemails:', error);
            setAlert({
                show: true,
                message: error.response?.data?.message || 'Error deleting voicemail',
                severity: 'error'
            });
        }
    };

    const handlePlay = (meta) => {
        try {
            const voicemailUrl = JSON.parse(meta).url + '.mp3';
            window.open(voicemailUrl, '_blank');
        } catch (error) {
            console.error('Error playing voicemail:', error);
            setAlert({
                show: true,
                message: 'Error playing voicemail',
                severity: 'error'
            });
        }
    };

    const toggleSelected = (callsid) => {
        setSelected((current) => (
            current.includes(callsid)
                ? current.filter((id) => id !== callsid)
                : current.concat(callsid)
        ));
    };

    const selectableCallsids = voicemails.map((voicemail) => voicemail.callsid).filter(Boolean);
    const allSelected = selectableCallsids.length > 0
        && selectableCallsids.every((callsid) => selected.includes(callsid));

    useEffect(() => {
        if (open) {
            fetchVoicemails();
        }
    }, [open, serviceBodyId]);

    return (
        <Dialog
            open={open}
            onClose={onClose}
            maxWidth="lg"
            fullWidth
        >
            <DialogTitle>
                Voicemail for {serviceBodyName}
            </DialogTitle>
            <DialogContent>
                <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                    Delete permanently removes the recording from Twilio. This cannot be undone.
                </Typography>
                {alert.show && (
                    <Alert
                        severity={alert.severity}
                        onClose={() => setAlert({ ...alert, show: false })}
                        sx={{ mb: 2 }}
                    >
                        {alert.message}
                    </Alert>
                )}
                <Button
                    variant="contained"
                    color="error"
                    disabled={selected.length === 0}
                    onClick={handleDeleteSelected}
                    sx={{ mb: 2 }}
                >
                    Delete selected
                </Button>
                <TableContainer component={Paper}>
                    <Table size="small">
                        <TableHead>
                            <TableRow>
                                <TableCell padding="checkbox">
                                    <Checkbox
                                        size="small"
                                        checked={allSelected}
                                        disabled={selectableCallsids.length === 0}
                                        onChange={() => setSelected(allSelected ? [] : selectableCallsids)}
                                        inputProps={{ 'aria-label': 'Select all voicemails' }}
                                    />
                                </TableCell>
                                <TableCell>Timestamp</TableCell>
                                <TableCell>CallSid</TableCell>
                                <TableCell>From</TableCell>
                                <TableCell>To</TableCell>
                                <TableCell>Pin</TableCell>
                                <TableCell>Actions</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {loading ? (
                                <TableRow>
                                    <TableCell colSpan={7} align="center">Loading...</TableCell>
                                </TableRow>
                            ) : voicemails.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} align="center">No voicemails found</TableCell>
                                </TableRow>
                            ) : (
                                voicemails.map((voicemail) => (
                                    <TableRow key={voicemail.callsid || voicemail.event_time}>
                                        <TableCell padding="checkbox">
                                            <Checkbox
                                                size="small"
                                                checked={selected.includes(voicemail.callsid)}
                                                disabled={!voicemail.callsid}
                                                onChange={() => toggleSelected(voicemail.callsid)}
                                                inputProps={{ 'aria-label': `Select voicemail ${voicemail.callsid}` }}
                                            />
                                        </TableCell>
                                        <TableCell>{new Date(voicemail.event_time).toLocaleString()}</TableCell>
                                        <TableCell>{voicemail.callsid}</TableCell>
                                        <TableCell>{voicemail.from_number}</TableCell>
                                        <TableCell>{voicemail.to_number}</TableCell>
                                        <TableCell>{voicemail.pin}</TableCell>
                                        <TableCell>
                                            {voicemail.meta && (
                                                <Tooltip title="Play">
                                                    <IconButton
                                                        size="small"
                                                        color="primary"
                                                        onClick={() => handlePlay(voicemail.meta)}
                                                    >
                                                        <PlayArrowIcon />
                                                    </IconButton>
                                                </Tooltip>
                                            )}
                                            <Tooltip title="Permanently delete recording from Twilio">
                                                <span>
                                                    <IconButton
                                                        size="small"
                                                        color="error"
                                                        disabled={!voicemail.callsid}
                                                        onClick={() => handleDelete(voicemail.callsid)}
                                                    >
                                                        <DeleteIcon />
                                                    </IconButton>
                                                </span>
                                            </Tooltip>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </TableContainer>
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose}>Close</Button>
            </DialogActions>
        </Dialog>
    );
}
