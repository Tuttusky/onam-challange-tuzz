import client from './client';

export const getBySlug = (slug) => client.get(`/cms/${slug}`);

export const list = () => client.get('/cms');
