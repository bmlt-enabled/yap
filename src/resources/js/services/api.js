// src/services/api.js

import axios from 'axios';

const apiClient = axios.create({
    baseURL: rootUrl,
    withCredentials: true,
    withXSRFToken: true
});

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

export default apiClient;
