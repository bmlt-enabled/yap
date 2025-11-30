import * as React from 'react';
import { Stack, MenuItem, ListItemIcon, ListItemText, Divider, Typography, Box } from '@mui/material';
import LockResetIcon from '@mui/icons-material/LockReset';
import LogoutIcon from '@mui/icons-material/Logout';
import { useSession } from '../SessionContext';
import { AuthenticationContext, SessionContext as ToolpadSessionContext } from '@toolpad/core/AppProvider';

export default function CustomAccountMenu(props) {
    const { openChangePassword } = useSession();
    const session = React.useContext(ToolpadSessionContext);
    const authentication = React.useContext(AuthenticationContext);

    const handleSignOut = () => {
        if (authentication?.signOut) {
            authentication.signOut();
        }
    };

    const handleChangePassword = () => {
        if (openChangePassword) {
            openChangePassword();
        }
    };

    return (
        <Stack direction="column" {...props} sx={{ minWidth: 200 }}>
            {/* Account Header */}
            <Box sx={{ p: 2 }}>
                <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                    {session?.user?.name || session?.user?.email || 'Account'}
                </Typography>
                {session?.user?.email && (
                    <Typography variant="caption" color="text.secondary">
                        {session.user.email}
                    </Typography>
                )}
            </Box>

            <Divider />

            {/* Change Password */}
            <MenuItem onClick={handleChangePassword}>
                <ListItemIcon>
                    <LockResetIcon fontSize="small" />
                </ListItemIcon>
                <ListItemText>Change Password</ListItemText>
            </MenuItem>

            <Divider />

            {/* Sign Out */}
            <MenuItem onClick={handleSignOut}>
                <ListItemIcon>
                    <LogoutIcon fontSize="small" />
                </ListItemIcon>
                <ListItemText>Sign out</ListItemText>
            </MenuItem>
        </Stack>
    );
}
