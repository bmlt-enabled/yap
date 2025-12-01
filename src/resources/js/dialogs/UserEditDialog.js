import { useState, useEffect } from "react";
import {
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    Button,
    TextField,
    FormControl,
    InputLabel,
    Select,
    MenuItem,
    OutlinedInput,
    Chip,
    Box,
    CircularProgress
} from "@mui/material";
import apiClient from "../services/api";

const ITEM_HEIGHT = 48;
const ITEM_PADDING_TOP = 8;
const MenuProps = {
    PaperProps: {
        style: {
            maxHeight: ITEM_HEIGHT * 4.5 + ITEM_PADDING_TOP,
            width: 250,
        },
    },
};

export default function UserEditDialog({ open, onClose, editMode, userData, showSnackbar }) {
    const [username, setUsername] = useState('');
    const [name, setName] = useState('');
    const [password, setPassword] = useState('');
    const [permissions, setPermissions] = useState([]);
    const [serviceBodies, setServiceBodies] = useState([]);
    const [availableServiceBodies, setAvailableServiceBodies] = useState([]);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (open) {
            loadServiceBodies();
            if (editMode && userData) {
                setUsername(userData.username || '');
                setName(userData.name || '');
                setPassword('');
                // Parse permissions and service bodies - handle both string and array formats
                if (Array.isArray(userData.permissions)) {
                    setPermissions(userData.permissions);
                } else if (typeof userData.permissions === 'string') {
                    setPermissions(userData.permissions.split(',').map(p => p.trim()).filter(Boolean));
                } else {
                    setPermissions([]);
                }

                if (Array.isArray(userData.service_bodies)) {
                    setServiceBodies(userData.service_bodies.map(s => typeof s === 'number' ? s : parseInt(s)));
                } else if (typeof userData.service_bodies === 'string') {
                    setServiceBodies(userData.service_bodies.split(',').map(s => parseInt(s.trim())).filter(Boolean));
                } else {
                    setServiceBodies([]);
                }
            } else {
                setUsername('');
                setName('');
                setPassword('');
                setPermissions([]);
                setServiceBodies([]);
            }
        }
    }, [open, editMode, userData]);

    const loadServiceBodies = async () => {
        setLoading(true);
        try {
            // Use the main service bodies endpoint to get ALL service bodies
            const response = await apiClient.get(`/api/v1/rootServer/serviceBodies`);
            setAvailableServiceBodies(response.data || []);
        } catch (error) {
            console.error('Error loading service bodies:', error);
            showSnackbar('Error loading service bodies', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async () => {
        if (!username.trim()) {
            showSnackbar('Username is required', 'error');
            return;
        }
        if (!name.trim()) {
            showSnackbar('Display name is required', 'error');
            return;
        }
        if (!editMode && !password.trim()) {
            showSnackbar('Password is required for new users', 'error');
            return;
        }

        setSaving(true);

        const payload = {
            name,
            permissions,
            service_bodies: serviceBodies
        };

        if (!editMode) {
            payload.username = username;
        }

        if (password.trim()) {
            payload.password = password;
        }

        try {
            if (editMode) {
                await apiClient.put(`/api/v1/users/${username}`, payload);
                showSnackbar('User updated successfully', 'success');
            } else {
                await apiClient.post(`/api/v1/users`, payload);
                showSnackbar('User created successfully', 'success');
            }
            onClose(true);
        } catch (error) {
            console.error('Error saving user:', error);
            showSnackbar('Could not save user', 'error');
        } finally {
            setSaving(false);
        }
    };

    return (
        <Dialog open={open} onClose={() => onClose(false)} maxWidth="sm" fullWidth>
            <DialogTitle>{editMode ? 'Edit User' : 'Add User'}</DialogTitle>
            <DialogContent>
                {loading ? (
                    <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
                        <CircularProgress />
                    </Box>
                ) : (
                    <>
                        <TextField
                            margin="dense"
                            label="Username"
                            type="text"
                            fullWidth
                            variant="outlined"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            disabled={editMode}
                            autoComplete="username"
                            sx={{ mb: 2 }}
                        />
                        <TextField
                            margin="dense"
                            label="Display Name"
                            type="text"
                            fullWidth
                            variant="outlined"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            sx={{ mb: 2 }}
                        />
                        <TextField
                            margin="dense"
                            label={editMode ? "Password (leave blank to keep current)" : "Password"}
                            type="password"
                            fullWidth
                            variant="outlined"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            autoComplete="new-password"
                            sx={{ mb: 2 }}
                        />
                        <FormControl fullWidth sx={{ mb: 2 }}>
                            <InputLabel>Permissions</InputLabel>
                            <Select
                                multiple
                                value={permissions}
                                onChange={(e) => setPermissions(e.target.value)}
                                input={<OutlinedInput label="Permissions" />}
                                renderValue={(selected) => (
                                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                        {selected.map((value) => (
                                            <Chip key={value} label={value === '1' ? 'Manage Users' : value} size="small" />
                                        ))}
                                    </Box>
                                )}
                                MenuProps={MenuProps}
                            >
                                <MenuItem value="1">Manage Users</MenuItem>
                            </Select>
                        </FormControl>
                        <FormControl fullWidth>
                            <InputLabel>Service Bodies Access</InputLabel>
                            <Select
                                multiple
                                value={serviceBodies}
                                onChange={(e) => setServiceBodies(e.target.value)}
                                input={<OutlinedInput label="Service Bodies Access" />}
                                renderValue={(selected) => (
                                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                        {selected.map((value) => {
                                            // Use == instead of === to handle string vs number comparison
                                            const sb = availableServiceBodies.find(s => s.id == value);
                                            return (
                                                <Chip
                                                    key={value}
                                                    label={sb ? `${sb.name} (${value})` : value}
                                                    size="small"
                                                />
                                            );
                                        })}
                                    </Box>
                                )}
                                MenuProps={MenuProps}
                            >
                                {availableServiceBodies.map((sb) => (
                                    <MenuItem key={sb.id} value={parseInt(sb.id)}>
                                        {sb.name} ({sb.id}) / {sb.parent_name} ({sb.parent_id})
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                    </>
                )}
            </DialogContent>
            <DialogActions>
                <Button onClick={() => onClose(false)} color="error" disabled={saving}>
                    Cancel
                </Button>
                <Button onClick={handleSave} color="primary" disabled={saving || loading}>
                    {saving ? <CircularProgress size={24} /> : 'Save'}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
