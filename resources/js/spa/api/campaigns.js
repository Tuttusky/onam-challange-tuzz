import client from './client';

export const getActive = () => client.get('/campaigns/active');

export const getBySlug = (slug) => client.get(`/campaigns/${slug}`);
