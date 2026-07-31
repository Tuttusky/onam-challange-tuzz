import client from './client';

export const get = (period = 'daily', params = {}) =>
    client.get(`/leaderboard/${period}`, { params });

export const getForCampaign = (period, campaignSlug, metric = null) =>
    get(period, {
        campaign_slug: campaignSlug,
        ...(metric ? { metric } : {}),
    });

export const getDaily = (campaignSlug, metric = null) => getForCampaign('daily', campaignSlug, metric);

export const getWeekly = (campaignSlug, metric = null) => getForCampaign('weekly', campaignSlug, metric);

export const getOverall = (campaignSlug, metric = null) => getForCampaign('overall', campaignSlug, metric);
