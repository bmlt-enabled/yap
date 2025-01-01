import * as React from 'react';
import { SignInPage } from '@toolpad/core/SignInPage';
import { useNavigate } from 'react-router-dom';
import { useSession } from '../SessionContext';
import apiClient from "../services/api";

export default function LoginPage() {
    const { setSession } = useSession();
    const navigate = useNavigate();

    const handleLogin = async (username, password) => {
        return new Promise((resolve, reject) => {
            setTimeout(async () => {
                try {
                    await apiClient.get('/sanctum/csrf-cookie');
                    const response = await apiClient.post('/api/v1/login', {username, password});
                    localStorage.setItem('token', response.data.token);

                    resolve(response.data);
                } catch (error) {
                    reject(error.response?.data || 'Login failed!')
                }
            }, 1000);
        });
    };

    const signIn = async (provider, formData, callbackUrl) => {
        try {
            const session = await handleLogin(formData.get('email'), formData.get('password'))
            console.log(session)
            if (session) {
                setSession(session);
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

    return (
        <SignInPage
            signIn={signIn}
            providers={[{ id: 'credentials', name: 'Username and Password' }]}
            slotProps={{ emailField: { autoFocus: true, label: 'Username', type: 'text', placeholder: 'Username' } }}
        />
    );
}
