import baseAxios from 'axios';

const axios = baseAxios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

axios.interceptors.request.use((config) => {
    const token = localStorage.getItem('api_token');
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
            localStorage.removeItem('api_token');
        }
        return Promise.reject(error);
    },
);

export default axios;
