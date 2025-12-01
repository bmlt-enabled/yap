import { useState, useEffect } from "react";
import {
    Box,
    Button,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Paper,
    Checkbox,
    Typography,
    Alert,
    Snackbar,
    IconButton,
    Chip
} from "@mui/material";
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';
import PeopleIcon from '@mui/icons-material/People';
import apiClient from "../services/api";
import UserEditDialog from "../dialogs/UserEditDialog";

// Permission mapping - matches AdminInterfaceRights constants
const getPermissionLabel = (permValue) => {
    const permStr = String(permValue).trim();
    switch (permStr) {
        case '1':
            return 'Manage Users';
        default:
            return permStr;
    }
};

function Users() {
    const [loading, setLoading] = useState(false);
    const [users, setUsers] = useState([]);
    const [showDialog, setShowDialog] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);
    const [currentUsername, setCurrentUsername] = useState('');
    const [isAdmin, setIsAdmin] = useState(false);
    const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' });
    const [serviceBodies, setServiceBodies] = useState([]);

    useEffect(() => {
        loadUsers();
        loadCurrentUser();
        loadServiceBodies();
    }, []);

    const loadCurrentUser = async () => {
        try {
            const response = await apiClient.get(`/api/v1/auth/check`);
            setCurrentUsername(response.data.username);
            setIsAdmin(response.data.is_admin);
        } catch (error) {
            console.error('Error loading current user:', error);
        }
    };

    const loadServiceBodies = async () => {
        try {
            // Use the main service bodies endpoint to get ALL service bodies, not just user's
            const response = await apiClient.get(`/api/v1/rootServer/serviceBodies`);
            setServiceBodies(response.data || []);
        } catch (error) {
            console.error('Error loading service bodies:', error);
        }
    };

    const loadUsers = async () => {
        setLoading(true);
        try {
            const response = await apiClient.get(`/api/v1/users`);
            setUsers(response.data || []);
        } catch (error) {
            console.error('Error fetching users:', error);
            showSnackbar('Error loading users', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleAddUser = () => {
        setEditMode(false);
        setSelectedUser(null);
        setShowDialog(true);
    };

    const handleEditUser = (user) => {
        setEditMode(true);
        setSelectedUser(user);
        setShowDialog(true);
    };

    const handleDeleteUser = async (username) => {
        if (!window.confirm(`Are you sure you want to delete user "${username}"?`)) {
            return;
        }

        try {
            await apiClient.delete(`/api/v1/users/${username}`);
            showSnackbar('User deleted successfully', 'success');
            await loadUsers();
        } catch (error) {
            console.error('Error deleting user:', error);
            showSnackbar('Error deleting user', 'error');
        }
    };

    const handleDialogClose = (shouldRefresh) => {
        setShowDialog(false);
        if (shouldRefresh) {
            loadUsers();
        }
    };

    const showSnackbar = (message, severity = 'success') => {
        setSnackbar({ open: true, message, severity });
    };

    const handleCloseSnackbar = () => {
        setSnackbar({ ...snackbar, open: false });
    };

    return (
        <Box sx={{ p: 3 }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                    <PeopleIcon sx={{ fontSize: 40, mr: 2 }} />
                    <Typography variant="h4">
                        Users
                    </Typography>
                </Box>
                <Button
                    variant="contained"
                    color="primary"
                    onClick={handleAddUser}
                >
                    Add User
                </Button>
            </Box>

            {loading ? (
                <Typography>Loading...</Typography>
            ) : (
                <TableContainer component={Paper}>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableCell>Actions</TableCell>
                                <TableCell>Username</TableCell>
                                <TableCell>Name</TableCell>
                                <TableCell>Service Bodies</TableCell>
                                <TableCell>Permissions</TableCell>
                                {isAdmin && <TableCell>Admin</TableCell>}
                                <TableCell>Date Created</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {users.map((user) => (
                                <TableRow key={user.username}>
                                    <TableCell>
                                        <Box sx={{ display: 'flex', gap: 0.5, alignItems: 'center' }}>
                                            <IconButton
                                                size="small"
                                                color="warning"
                                                onClick={() => handleEditUser(user)}
                                                title="Edit"
                                            >
                                                <EditIcon fontSize="small" />
                                            </IconButton>
                                            {user.username !== currentUsername && (
                                                <IconButton
                                                    size="small"
                                                    color="error"
                                                    onClick={() => handleDeleteUser(user.username)}
                                                    title="Delete"
                                                >
                                                    <DeleteIcon fontSize="small" />
                                                </IconButton>
                                            )}
                                        </Box>
                                    </TableCell>
                                    <TableCell>{user.username}</TableCell>
                                    <TableCell>{user.name}</TableCell>
                                    <TableCell>
                                        <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                            {(() => {
                                                // Parse service bodies - handle both array and string formats
                                                let sbIds = [];
                                                if (Array.isArray(user.service_bodies)) {
                                                    sbIds = user.service_bodies.map(id => typeof id === 'number' ? id : parseInt(id));
                                                } else if (typeof user.service_bodies === 'string' && user.service_bodies.trim()) {
                                                    sbIds = user.service_bodies.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
                                                }

                                                return sbIds.length > 0 ? sbIds.map((sbId) => {
                                                    // Use == instead of === to handle string vs number comparison
                                                    const sb = serviceBodies.find(s => s.id == sbId);
                                                    return (
                                                        <Chip
                                                            key={sbId}
                                                            label={sb ? `${sb.name} (${sbId})` : sbId}
                                                            size="small"
                                                        />
                                                    );
                                                }) : <span>{user.service_bodies}</span>;
                                            })()}
                                        </Box>
                                    </TableCell>
                                    <TableCell>
                                        <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                            {(() => {
                                                // Parse permissions - handle array, string, and number formats
                                                let perms = [];
                                                if (Array.isArray(user.permissions)) {
                                                    perms = user.permissions.filter(p => p !== 0 && p !== '0');
                                                } else if (typeof user.permissions === 'string' && user.permissions.trim()) {
                                                    perms = user.permissions.split(',').map(p => p.trim()).filter(p => p && p !== '0');
                                                } else if (typeof user.permissions === 'number' || (user.permissions !== null && user.permissions !== undefined && !isNaN(user.permissions))) {
                                                    // Handle numeric permissions (like 1, 2, etc.) but exclude 0
                                                    if (user.permissions !== 0 && user.permissions !== '0') {
                                                        perms = [String(user.permissions)];
                                                    }
                                                }

                                                const chips = [];

                                                // Add admin chip if user is admin
                                                if (user.is_admin === 1 || user.is_admin === true) {
                                                    chips.push(
                                                        <Chip
                                                            key="admin"
                                                            label="Admin"
                                                            size="small"
                                                            color="error"
                                                        />
                                                    );
                                                }

                                                // Add permission chips
                                                perms.forEach((perm, idx) => {
                                                    chips.push(
                                                        <Chip
                                                            key={idx}
                                                            label={getPermissionLabel(perm)}
                                                            size="small"
                                                            color="primary"
                                                            variant="outlined"
                                                        />
                                                    );
                                                });

                                                return chips.length > 0 ? chips : <span>-</span>;
                                            })()}
                                        </Box>
                                    </TableCell>
                                    {isAdmin && (
                                        <TableCell>
                                            <Checkbox
                                                checked={!!user.is_admin}
                                                disabled
                                            />
                                        </TableCell>
                                    )}
                                    <TableCell>{user.created_on}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableContainer>
            )}

            {showDialog && (
                <UserEditDialog
                    open={showDialog}
                    onClose={handleDialogClose}
                    editMode={editMode}
                    userData={selectedUser}
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

export default Users;
