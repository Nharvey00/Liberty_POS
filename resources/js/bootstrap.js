import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Global CSRF / Session Expiry Interceptor
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 419) {
            alert('Session expired. Please refresh the page.');
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
