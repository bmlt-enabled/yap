import { useState } from "react";
import {
    Dialog,
    DialogTitle,
    DialogContent,
    DialogActions,
    Button,
    TextField,
    CircularProgress,
    Alert
} from "@mui/material";
import apiClient from "../services/api";

export default function ChangePasswordDialog({ open, onClose, username }) {
    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);

    const handleClose = () => {
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
        setError('');
        setSuccess(false);
        onClose();
    };

    const handleSave = async () => {
        setError('');
        setSuccess(false);

        if (!currentPassword.trim()) {
            setError('Current password is required');
            return;
        }

        if (!newPassword.trim()) {
            setError('New password is required');
            return;
        }

        if (newPassword !== confirmPassword) {
            setError('New passwords do not match');
            return;
        }

        if (newPassword.length < 8) {
            setError('New password must be at least 8 characters');
            return;
        }

        setSaving(true);

        try {
            await apiClient.put(`/api/v1/users/${username}`, {
                current_password: currentPassword,
                password: newPassword
            });
            setSuccess(true);
            setTimeout(() => {
                handleClose();
            }, 1500);
        } catch (error) {
            console.error('Error changing password:', error);
            if (error.response?.status === 401) {
                setError('Current password is incorrect');
            } else if (error.response?.data?.message) {
                setError(error.response.data.message);
            } else {
                setError('Could not change password. Please try again.');
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <Dialog open={open} onClose={handleClose} maxWidth="sm" fullWidth>
            <DialogTitle>Change Password</DialogTitle>
            <DialogContent>
                {error && (
                    <Alert severity="error" sx={{ mb: 2 }}>
                        {error}
                    </Alert>
                )}
                {success && (
                    <Alert severity="success" sx={{ mb: 2 }}>
                        Password changed successfully!
                    </Alert>
                )}
                <TextField
                    margin="dense"
                    label="Current Password"
                    type="password"
                    fullWidth
                    variant="outlined"
                    value={currentPassword}
                    onChange={(e) => setCurrentPassword(e.target.value)}
                    autoComplete="current-password"
                    disabled={saving || success}
                    sx={{ mb: 2 }}
                />
                <TextField
                    margin="dense"
                    label="New Password"
                    type="password"
                    fullWidth
                    variant="outlined"
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                    autoComplete="new-password"
                    disabled={saving || success}
                    helperText="Must be at least 8 characters"
                    sx={{ mb: 2 }}
                />
                <TextField
                    margin="dense"
                    label="Confirm New Password"
                    type="password"
                    fullWidth
                    variant="outlined"
                    value={confirmPassword}
                    onChange={(e) => setConfirmPassword(e.target.value)}
                    autoComplete="new-password"
                    disabled={saving || success}
                />
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose} color="error" disabled={saving}>
                    Cancel
                </Button>
                <Button onClick={handleSave} color="primary" disabled={saving || success}>
                    {saving ? <CircularProgress size={24} /> : 'Change Password'}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
