import client from './client';

export const upload = (formData) =>
    client.post('/friend-media', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });

export const getAvatars = () => client.get('/friend-media/avatars');
