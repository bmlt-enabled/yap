import React, { useEffect, useState } from 'react';
import {
    Button,
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
    Tooltip
} from '@mui/material';
import PlayArrowIcon from '@mui/icons-material/PlayArrow';
import DeleteIcon from '@mui/icons-material/Delete';
import apiClient from '../services/api';

export function VoicemailDialog({ open, onClose, serviceBodyId, serviceBodyName }) {
    const [voicemails, setVoicemails] = useState([]);
    const [loading, setLoading] = useState(true);
    const [alert, setAlert] = useState({ show: false, message: '', severity: 'success' });

    const fetchVoicemails = async () => {
        try {
            const response = await apiClient.get(`/api/v1/voicemail?serviceBodyId=${serviceBodyId}`);
            setVoicemails(response.data.data);
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
        try {
            await apiClient.delete(`/api/v1/voicemail/${callsid}?serviceBodyId=${serviceBodyId}`);
            setAlert({
                show: true,
                message: 'Voicemail deleted successfully',
                severity: 'success'
            });
            fetchVoicemails();
        } catch (error) {
            console.error('Error deleting voicemail:', error);
            setAlert({
                show: true,
                message: 'Error deleting voicemail',
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
                {alert.show && (
                    <Alert 
                        severity={alert.severity} 
                        onClose={() => setAlert({ ...alert, show: false })}
                        sx={{ mb: 2 }}
                    >
                        {alert.message}
                    </Alert>
                )}
                <TableContainer component={Paper}>
                    <Table size="small">
                        <TableHead>
                            <TableRow>
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
                                    <TableCell colSpan={6} align="center">Loading...</TableCell>
                                </TableRow>
                            ) : voicemails.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} align="center">No voicemails found</TableCell>
                                </TableRow>
                            ) : (
                                voicemails.map((voicemail) => (
                                    <TableRow key={voicemail.callsid}>
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
                                            <Tooltip title="Delete">
                                                <IconButton 
                                                    size="small" 
                                                    color="error"
                                                    onClick={() => handleDelete(voicemail.callsid)}
                                                >
                                                    <DeleteIcon />
                                                </IconButton>
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