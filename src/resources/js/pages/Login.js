// src/resources/js/pages/Login.js
import React, { useState } from 'react';
import { TextField, Button, Card, CardContent, Typography } from '@mui/material';

function Login() {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');

        try {
            const response = await axios.post(`${rootUrl}/api/v1/login`, {
                username, // Updated to username
                password,
            });

            // Handle successful login (e.g., store token, redirect)
            console.log('Login successful:', response.data);
            window.location.href = `${baseUrl}/`; // Redirect after login

        } catch (err) {
            if (err.response && err.response.data && err.response.data.message) {
                setError(err.response.data.message);
            } else {
                setError('An error occurred during login.');
            }
            console.error('Login failed:', err);
        }
    };

    return (
        <Card>
            <CardContent>
                <Typography variant="h5" component="h2" gutterBottom>
                    Login
                </Typography>
                {error && <Typography color="error">{error}</Typography>}
                <form onSubmit={handleSubmit}>
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

