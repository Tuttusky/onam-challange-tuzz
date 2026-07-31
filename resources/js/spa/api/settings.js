import client from './client';

export const getPublic = () => client.get('/settings/public');

export const getAnalytics = () => client.get('/settings/analytics');
