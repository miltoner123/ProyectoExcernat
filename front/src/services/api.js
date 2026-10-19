
import api from '../services/api';

const api = axios.create({
    baseURL: 'http://localhost:8000',

    withCredentials: true,
    withXSRFToken: true,

    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    }
});

export default api;
