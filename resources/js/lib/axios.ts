import { clearToken, getToken } from '@/lib/token-storage';
import baseAxios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL ?? '';

const axios = baseAxios.create({
    baseURL,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

axios.interceptors.request.use((config) => {
    const token = getToken();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Clear stale token; ProtectedRoute will handle the redirect
            clearToken();
        }
        return Promise.reject(error);
    },
);

export default axios;
