// src/services/api.js

import axios from 'axios';

// Use rootUrl as baseURL so API calls work in subdirectories
// rootUrl will be empty string for root deployments, or "/yap-sezf" for subdirectory deployments
const apiClient = axios.create({
    baseURL: typeof rootUrl !== 'undefined' ? rootUrl : '/',
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

apiClient.interceptors.response.use(
    response => response,
    error => {
        if (error.response && (error.response.status === 401 || error.response.status === 419)) {
            // Session expired - redirect to login
            localStorage.removeItem('session');
            const basePath = typeof rootUrl !== 'undefined' && rootUrl ? `${rootUrl}/${baseUrl}` : `/${baseUrl}`;
            window.location.href = `${basePath}/login`;
        }
        return Promise.reject(error);
    }
);

export default apiClient;
