// src/resources/js/pages/Login.js
import React, { useState } from 'react';
import { TextField, Button, Card, CardContent, Typography } from '@mui/material';
import apiClient from '../services/api';
import {useNavigate} from "react-router-dom";

function Login() {
    const navigate = useNavigate();
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [loginError, setLoginError] = useState(null);

    const handleLogin = async (e) => {
        e.preventDefault();

        try {
            // Get CSRF token
            await apiClient.get('/sanctum/csrf-cookie');

            // Perform login
            const response = await apiClient.post('/api/v1/login', { username, password });

            // Store token in localStorage
            localStorage.setItem('token', response.data.token); // Adjust key if token is under a different property

            console.log('Login successful!', response.data);

            // Navigate to home page
            navigate(`${baseUrl}/home`);
        } catch (error) {
            console.error('Login error:', error);
            setLoginError(error.response?.data?.message || 'Login failed!')
        }
    };

    return (
        <Card>
            <CardContent>
                <Typography variant="h5" component="h2" gutterBottom>
                    Login
                </Typography>
                {loginError && <Typography color="error">{loginError}</Typography>}
                <form onSubmit={handleLogin}>
                    <TextField
                        label="Username" // Updated label
                        type="text"  // Updated type
                        fullWidth
                        value={username}
                        onChange={(e) => setUsername(e.target.value)}
                        margin="normal"
                    />
                    <TextField
                        label="Password"
                        type="password"
                        fullWidth
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        margin="normal"
                    />
                    <Button type="submit" variant="contained" color="primary">
                        Login
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

export default Login;

