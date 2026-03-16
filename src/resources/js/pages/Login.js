import * as React from 'react';
import { SignInPage } from '@toolpad/core/SignInPage';
import { useNavigate } from 'react-router-dom';
import { useSession } from '../SessionContext';
import { useLocalization } from '../contexts/LocalizationContext';
import { useColorScheme } from '@mui/material/styles';
import { IconButton, Tooltip, Select, MenuItem, Stack } from '@mui/material';
import Brightness4Icon from '@mui/icons-material/Brightness4';
import Brightness7Icon from '@mui/icons-material/Brightness7';
import LanguageIcon from '@mui/icons-material/Language';
import apiClient from "../services/api";
import AVAILABLE_LANGUAGES from '../constants/languages';

export default function LoginPage() {
    const { setSession } = useSession();
    const { refreshLocalizations, getWord } = useLocalization();
    const navigate = useNavigate();
    const [version, setVersion] = React.useState('');
    const { mode, setMode } = useColorScheme();
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

    React.useEffect(() => {
        const fetchVersion = async () => {
            try {
                console.log('Fetching version...');
                const response = await apiClient.get('/api/v1/version');
                console.log('Version response:', response.data);
                setVersion(response.data.version);
            } catch (error) {
                console.error('Failed to fetch version:', error);
            }
        };
        fetchVersion();
    }, []);

    const handleLogin = async (username, password) => {
        return new Promise((resolve, reject) => {
            setTimeout(async () => {
                try {
                    localStorage.removeItem('session');
                    await apiClient.get('/sanctum/csrf-cookie');
                    const response = await apiClient.post('/api/v1/login', {
                        username,
                        password,
                        language: localStorage.getItem('preferredLanguage') || 'en-US'
                    });
                    localStorage.setItem('session', JSON.stringify(response.data));
                    resolve(response.data);
                } catch (error) {
                    reject(error || 'Login failed!')
                }
            }, 1000);
        });
    };

    const signIn = async (provider, formData, callbackUrl) => {
        try {
            const loginSession = await handleLogin(formData.get('email'), formData.get('password'))
            if (loginSession) {
                setSession(loginSession);
                // Refresh localizations to load the selected language
                await refreshLocalizations();
                navigate('/dashboard', { replace: true });
                return {};
            }
        } catch (error) {
            return {
                error: error instanceof Error ? error.message : 'An error occurred',
            };
        }
        return {};
    };

    console.log('Current version state:', version);

    return (
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', minHeight: '100vh' }}>
            <Stack
                direction="row"
                spacing={1}
                alignItems="center"
                sx={{
                    position: 'absolute',
                    top: 16,
                    right: 16,
                }}
            >
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
                    <IconButton onClick={toggleColorMode}>
                        {mode === 'dark' ? <Brightness7Icon /> : <Brightness4Icon />}
                    </IconButton>
                </Tooltip>
            </Stack>
            <SignInPage
                signIn={signIn}
                providers={[{ id: 'credentials', name: 'Username and Password' }]}
                localeText={{ signInTitle: getWord('authenticate') }}
                slotProps={{
                    emailField: {
                        autoFocus: true,
                        label: getWord('username'),
                        type: 'text',
                        placeholder: getWord('username'),
                    },
                    passwordField: {
                        label: getWord('password'),
                    },
                }}
            />
            <div style={{
                marginTop: '1rem',
                color: mode === 'dark' ? '#aaa' : '#666',
                fontSize: '0.875rem',
                position: 'fixed',
                bottom: '1rem',
                left: '50%',
                transform: 'translateX(-50%)'
            }}>
                {version ? `Version ${version}` : 'Loading version...'}
            </div>
        </div>
    );
}
