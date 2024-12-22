// src/services/api.js

import axios from 'axios';

const apiClient = axios.create({
    baseURL: rootUrl,
    withCredentials: true,
    withXSRFToken: true
});

export default apiClient;
