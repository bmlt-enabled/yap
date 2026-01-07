import * as React from 'react';
import { SignInPage } from '@toolpad/core/SignInPage';
import { useNavigate } from 'react-router-dom';
import { useSession } from '../SessionContext';
import apiClient from "../services/api";
import { useColorScheme } from '@mui/material/styles';
import { IconButton, Tooltip } from '@mui/material';
import Brightness4Icon from '@mui/icons-material/Brightness4';
import Brightness7Icon from '@mui/icons-material/Brightness7';

export default function LoginPage() {
    const { setSession } = useSession();
    const navigate = useNavigate();
    const [version, setVersion] = React.useState('');
    const { mode, systemMode, setMode } = useColorScheme();

    // Resolve actual mode (system mode resolves to light/dark based on OS preference)
    const resolvedMode = mode === 'system' ? systemMode : mode;

    const toggleColorMode = () => {
        setMode(resolvedMode === 'dark' ? 'light' : 'dark');
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
                    const response = await apiClient.post('/api/v1/login', {username, password});
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
            <SignInPage
                signIn={signIn}
                providers={[{ id: 'credentials', name: 'Username and Password' }]}
                slotProps={{ emailField: { autoFocus: true, label: 'Username', type: 'text', placeholder: 'Username' } }}
            />
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', marginTop: '-12rem' }}>
                <Tooltip title={`Switch to ${resolvedMode === 'dark' ? 'light' : 'dark'} mode`}>
                    <IconButton onClick={toggleColorMode} color="inherit">
                        {resolvedMode === 'dark' ? <Brightness7Icon /> : <Brightness4Icon />}
                    </IconButton>
                </Tooltip>
                <div style={{ color: '#666', fontSize: '0.875rem', marginTop: '0.5rem' }}>
                    {version ? `Version ${version}` : 'Loading version...'}
                </div>
            </div>
        </div>
    );
}
