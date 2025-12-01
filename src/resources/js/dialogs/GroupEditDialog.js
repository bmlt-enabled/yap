import React, { useState, useEffect } from "react";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
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

export default function GroupEditDialog({
    open,
    onClose,
    serviceBodyId,
    groupId,
    groupData,
    editMode,
    showSnackbar
}) {
    const [groupName, setGroupName] = useState('');
    const [sharedServiceBodies, setSharedServiceBodies] = useState([]);
    const [allServiceBodies, setAllServiceBodies] = useState([]);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [validationError, setValidationError] = useState('');

    useEffect(() => {
        if (open) {
            loadServiceBodies();
            if (editMode && groupData) {
                setGroupName(groupData.data[0]?.group_name || '');
                setSharedServiceBodies(groupData.data[0]?.group_shared_service_bodies || []);
            } else {
                setGroupName('');
                setSharedServiceBodies([]);
            }
            setValidationError('');
        }
    }, [open, editMode, groupData]);

    const loadServiceBodies = async () => {
        setLoading(true);
        try {
            const response = await apiClient.get(`/api/v1/rootServer/serviceBodies`);
            setAllServiceBodies(response.data || []);
        } catch (error) {
            console.error('Error fetching service bodies:', error);
            showSnackbar('Error loading service bodies', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleSharedServiceBodiesChange = (event) => {
        const {
            target: { value },
        } = event;
        setSharedServiceBodies(
            typeof value === 'string' ? value.split(',') : value,
        );
    };

    const handleSave = async () => {
        if (!groupName.trim()) {
            setValidationError('A name is required.');
            return;
        }

        setSaving(true);
        setValidationError('');

        const payload = {
            group_name: groupName,
            group_shared_service_bodies: sharedServiceBodies
        };

        try {
            if (editMode) {
                await apiClient.put(`/api/v1/groups/${groupId}`, payload);
                showSnackbar('Group updated successfully', 'success');
            } else {
                await apiClient.post(`/api/v1/groups?serviceBodyId=${serviceBodyId}`, payload);
                showSnackbar('Group created successfully', 'success');
            }
            onClose(true); // true indicates refresh is needed
        } catch (error) {
            console.error('Error saving group:', error);
            showSnackbar('Could not save group', 'error');
        } finally {
            setSaving(false);
        }
    };

    const handleCancel = () => {
        onClose(false);
    };

    return (
        <Dialog open={open} onClose={handleCancel} maxWidth="sm" fullWidth>
            <DialogTitle>
                {editMode ? 'Edit Group' : 'Add Group'}
            </DialogTitle>
            <DialogContent>
                {loading ? (
                    <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
                        <CircularProgress />
                    </Box>
                ) : (
                    <>
                        <TextField
                            autoFocus
                            margin="dense"
                            label="Name (required)"
                            type="text"
                            fullWidth
                            variant="outlined"
                            value={groupName}
                            onChange={(e) => setGroupName(e.target.value)}
                            error={!!validationError}
                            helperText={validationError}
                            sx={{ mb: 2 }}
                        />
                        <FormControl fullWidth>
                            <InputLabel id="shared-service-bodies-label">
                                Shared With Service Bodies (optional)
                            </InputLabel>
                            <Select
                                labelId="shared-service-bodies-label"
                                multiple
                                value={sharedServiceBodies}
                                onChange={handleSharedServiceBodiesChange}
                                input={<OutlinedInput label="Shared With Service Bodies (optional)" />}
                                renderValue={(selected) => (
                                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                        {selected.map((value) => {
                                            const serviceBody = allServiceBodies.find(sb => sb.id === value);
                                            return (
                                                <Chip
                                                    key={value}
                                                    label={serviceBody ? `${serviceBody.name} (${value})` : value}
                                                    size="small"
                                                />
                                            );
                                        })}
                                    </Box>
                                )}
                                MenuProps={MenuProps}
                            >
                                {allServiceBodies.map((serviceBody) => (
                                    <MenuItem key={serviceBody.id} value={serviceBody.id}>
                                        {serviceBody.name} ({serviceBody.id})
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                    </>
                )}
            </DialogContent>
            <DialogActions>
                <Button onClick={handleCancel} color="error" disabled={saving}>
                    Cancel
                </Button>
                <Button onClick={handleSave} color="primary" disabled={saving || loading}>
                    {saving ? <CircularProgress size={24} /> : 'OK'}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
