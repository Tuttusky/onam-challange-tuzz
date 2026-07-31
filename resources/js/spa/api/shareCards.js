import client from './client';

export const getCard = (token) => client.get(`/share-cards/${token}`);
