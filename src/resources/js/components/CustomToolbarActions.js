import * as React from 'react';
import { Stack, IconButton, Tooltip } from '@mui/material';
import { useColorScheme } from '@mui/material/styles';
import Brightness4Icon from '@mui/icons-material/Brightness4';
import Brightness7Icon from '@mui/icons-material/Brightness7';
import { Account } from '@toolpad/core/Account';
import CustomAccountMenu from './CustomAccountMenu';

export default function CustomToolbarActions() {
    const { mode, setMode } = useColorScheme();

    const toggleColorMode = () => {
        setMode(mode === 'dark' ? 'light' : 'dark');
    };

    return (
        <Stack direction="row" alignItems="center" spacing={1}>
            <Tooltip title={`Switch to ${mode === 'dark' ? 'light' : 'dark'} mode`}>
                <IconButton onClick={toggleColorMode} color="inherit">
                    {mode === 'dark' ? <Brightness7Icon /> : <Brightness4Icon />}
                </IconButton>
            </Tooltip>
            <Account
                slots={{
                    popoverContent: CustomAccountMenu,
                }}
            />
        </Stack>
    );
}
