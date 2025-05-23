import * as React from 'react';
import { SignInPage } from '@toolpad/core/SignInPage';
import { useNavigate } from 'react-router-dom';
import { useSession } from '../SessionContext';
import apiClient from "../services/api";

export default function LoginPage() {
    const { setSession } = useSession();
    const navigate = useNavigate();
    const [version, setVersion] = React.useState('');

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
                navigate(`/${baseUrl}/dashboard`, { replace: true });
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
            <div style={{ 
                marginTop: '1rem', 
                color: '#666', 
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
