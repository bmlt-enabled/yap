import * as React from 'react';
import { Stack, IconButton, Tooltip, Select, MenuItem } from '@mui/material';
import { useColorScheme } from '@mui/material/styles';
import Brightness4Icon from '@mui/icons-material/Brightness4';
import Brightness7Icon from '@mui/icons-material/Brightness7';
import LanguageIcon from '@mui/icons-material/Language';
import { Account } from '@toolpad/core/Account';
import CustomAccountMenu from './CustomAccountMenu';
import { useLocalization } from '../contexts/LocalizationContext';
import AVAILABLE_LANGUAGES from '../constants/languages';

export default function CustomToolbarActions() {
    const { mode, setMode } = useColorScheme();
    const { refreshLocalizations } = useLocalization();
    const [language, setLanguage] = React.useState(() => {
        return localStorage.getItem('preferredLanguage') || 'en-US';
    });

    const toggleColorMode = () => {
        setMode(mode === 'dark' ? 'light' : 'dark');
    };

    const handleLanguageChange = async (event) => {
        const newLanguage = event.target.value;
        setLanguage(newLanguage);
        localStorage.setItem('preferredLanguage', newLanguage);
        await refreshLocalizations();
    };

    return (
        <Stack direction="row" alignItems="center" spacing={1}>
            <Select
                value={language}
                onChange={handleLanguageChange}
                size="small"
                startAdornment={<LanguageIcon sx={{ mr: 1, color: 'action.active' }} />}
                sx={{
                    minWidth: 140,
                    '& .MuiSelect-select': {
                        display: 'flex',
                        alignItems: 'center',
                    },
                }}
            >
                {AVAILABLE_LANGUAGES.map((lang) => (
                    <MenuItem key={lang.code} value={lang.code}>
                        {lang.label}
                    </MenuItem>
                ))}
            </Select>
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
