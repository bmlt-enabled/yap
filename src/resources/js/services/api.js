// src/services/api.js

import axios from 'axios';

const apiClient = axios.create({
    baseURL: rootUrl,
    withCredentials: true,
    withXSRFToken: true
});

apiClient.interceptors.request.use((config) => {
    const localStorageSession = localStorage.getItem('session');
    if (localStorageSession) {
        config.headers['Authorization'] = `Bearer ${JSON.parse(localStorageSession).token}`;
    }
    return config;
}, (error) => {
    return Promise.reject(error);
});

export default apiClient;
