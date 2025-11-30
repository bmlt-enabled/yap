import { useState, useEffect } from "react";
import {
    Box,
    Button,
    CircularProgress,
    FormControl,
    InputLabel,
    MenuItem,
    Select,
    Typography,
    Alert,
    Snackbar,
    Divider
} from "@mui/material";
import { useLocalization } from "../contexts/LocalizationContext";
import apiClient from "../services/api";
import GroupEditDialog from "../dialogs/GroupEditDialog";
import VolunteersManager from "../components/VolunteersManager";

function Groups() {
    const { getWord } = useLocalization();
    const [serviceBodyId, setServiceBodyId] = useState(0);
    const [serviceBodies, setServiceBodies] = useState([]);
    const [groups, setGroups] = useState([]);
    const [selectedGroupId, setSelectedGroupId] = useState(0);
    const [loading, setLoading] = useState(false);
    const [loadingServiceBodies, setLoadingServiceBodies] = useState(true);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' });

    // Load service bodies on mount
    useEffect(() => {
        const loadServiceBodies = async () => {
            setLoadingServiceBodies(true);
            try {
                const response = await apiClient.get(`${rootUrl}/api/v1/rootServer/serviceBodies/user`);
                setServiceBodies(response.data || []);
            } catch (error) {
                console.error('Error fetching service bodies:', error);
                showSnackbar('Error loading service bodies', 'error');
            } finally {
                setLoadingServiceBodies(false);
            }
        };
        loadServiceBodies();
    }, []);

    const loadGroups = async (serviceBodyId) => {
        if (!serviceBodyId || serviceBodyId === 0) {
            setGroups([]);
            return;
        }

        setLoading(true);
        try {
            const response = await apiClient.get(`${rootUrl}/api/v1/groups?serviceBodyId=${serviceBodyId}&manage=1`);
            setGroups(response.data || []);
        } catch (error) {
            console.error('Error fetching groups:', error);
            showSnackbar('Error loading groups', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleServiceBodyChange = (newServiceBodyId) => {
        setServiceBodyId(newServiceBodyId);
        setSelectedGroupId(0);
        loadGroups(newServiceBodyId);
    };

    const handleGroupChange = (event) => {
        setSelectedGroupId(event.target.value);
    };

    const handleAddGroup = () => {
        setEditMode(false);
        setShowEditDialog(true);
    };

    const handleEditGroup = () => {
        setEditMode(true);
        setShowEditDialog(true);
    };

    const handleDeleteGroup = async () => {
        if (!window.confirm('Are you sure you want to delete this group?')) {
            return;
        }

        setLoading(true);
        try {
            await apiClient.delete(`${rootUrl}/api/v1/groups/${selectedGroupId}`);
            showSnackbar('Group deleted successfully', 'success');
            setSelectedGroupId(0);
            await loadGroups(serviceBodyId);
        } catch (error) {
            console.error('Error deleting group:', error);
            showSnackbar('Error deleting group', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleDialogClose = (shouldRefresh) => {
        setShowEditDialog(false);
        if (shouldRefresh) {
            loadGroups(serviceBodyId);
        }
    };

    const showSnackbar = (message, severity = 'success') => {
        setSnackbar({ open: true, message, severity });
    };

    const handleCloseSnackbar = () => {
        setSnackbar({ ...snackbar, open: false });
    };

    const selectedGroup = groups.find(g => g.id === selectedGroupId);

    return (
        <Box sx={{ p: 3 }}>
            <Typography variant="h4" gutterBottom>
                {getWord('groups') || 'Groups'}
            </Typography>

            <Box sx={{ mb: 3 }}>
                {loadingServiceBodies ? (
                    <CircularProgress />
                ) : (
                    <FormControl fullWidth>
                        <InputLabel>{getWord('service_bodies') || 'Service Bodies'}</InputLabel>
                        <Select
                            value={serviceBodyId}
                            onChange={(e) => handleServiceBodyChange(e.target.value)}
                            label={getWord('service_bodies') || 'Service Bodies'}
                        >
                            <MenuItem value={0}>-= {getWord('select_a_service_body') || 'Select a Service Body'} =-</MenuItem>
                            {serviceBodies.map((sb) => (
                                <MenuItem key={sb.id} value={sb.id}>
                                    {sb.name} ({sb.id}) / {sb.parent_name} ({sb.parent_id})
                                </MenuItem>
                            ))}
                        </Select>
                    </FormControl>
                )}
            </Box>

            {serviceBodyId > 0 && (
                <>
                    <Box sx={{ display: 'flex', gap: 2, alignItems: 'flex-end', mb: 3 }}>
                        <FormControl sx={{ minWidth: 300, flex: 1 }}>
                            <InputLabel>{getWord('groups') || 'Groups'}</InputLabel>
                            <Select
                                value={selectedGroupId}
                                onChange={handleGroupChange}
                                label={getWord('groups') || 'Groups'}
                                disabled={loading}
                            >
                                <MenuItem value={0}>
                                    -= {getWord('select_a_group') || 'Select A Group'} =-
                                </MenuItem>
                                {groups.map((group) => (
                                    <MenuItem key={group.id} value={group.id}>
                                        {group.data[0]?.group_name || 'Unnamed Group'} ({group.id})
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>

                        <Button
                            variant="contained"
                            color="primary"
                            onClick={handleAddGroup}
                        >
                            {getWord('create') || 'Create'}
                        </Button>

                        {selectedGroupId > 0 && (
                            <>
                                <Button
                                    variant="contained"
                                    color="warning"
                                    onClick={handleEditGroup}
                                >
                                    {getWord('edit') || 'Edit'}
                                </Button>
                                <Button
                                    variant="contained"
                                    color="error"
                                    onClick={handleDeleteGroup}
                                    disabled={loading}
                                >
                                    {getWord('delete') || 'Delete'}
                                </Button>
                            </>
                        )}
                    </Box>

                    {loading && (
                        <Box sx={{ display: 'flex', justifyContent: 'center', mt: 4 }}>
                            <CircularProgress />
                        </Box>
                    )}

                    {selectedGroupId > 0 && !loading && (
                        <>
                            <Divider sx={{ my: 3 }} />
                            <Typography variant="h5" gutterBottom>
                                {getWord('volunteers') || 'Volunteers'}
                            </Typography>
                            <VolunteersManager
                                serviceBodyId={serviceBodyId}
                                groupId={selectedGroupId}
                            />
                        </>
                    )}
                </>
            )}

            {showEditDialog && (
                <GroupEditDialog
                    open={showEditDialog}
                    onClose={handleDialogClose}
                    serviceBodyId={serviceBodyId}
                    groupId={editMode ? selectedGroupId : null}
                    groupData={editMode ? selectedGroup : null}
                    editMode={editMode}
                    showSnackbar={showSnackbar}
                />
            )}

            <Snackbar
                open={snackbar.open}
                autoHideDuration={3000}
                onClose={handleCloseSnackbar}
                anchorOrigin={{ vertical: 'top', horizontal: 'center' }}
            >
                <Alert onClose={handleCloseSnackbar} severity={snackbar.severity} sx={{ width: '100%' }}>
                    {snackbar.message}
                </Alert>
            </Snackbar>
        </Box>
    );
}

export default Groups;
