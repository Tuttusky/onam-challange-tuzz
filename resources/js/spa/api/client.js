import axios from 'axios';

const SESSION_KEY = 'player_session_token';

export const getSessionToken = () => localStorage.getItem(SESSION_KEY);

export const setSessionToken = (token) => {
    if (token) {
        localStorage.setItem(SESSION_KEY, token);
    } else {
        localStorage.removeItem(SESSION_KEY);
    }
};

const client = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

client.interceptors.request.use((config) => {
    const token = getSessionToken();
    if (token) {
        config.headers['X-Player-Session'] = token;
    }
    return config;
});

client.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            setSessionToken(null);
        }
        return Promise.reject(error);
    }
);

export default client;
