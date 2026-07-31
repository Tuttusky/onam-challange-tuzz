import client from './client';

export const start = (campaignSlug, payload) =>
    client.post('/sessions/start', {
        campaign_slug: campaignSlug,
        name: payload.name,
        player_uuid: payload.uuid ?? payload.player_uuid ?? null,
        referral_code: payload.referral_code ?? null,
        friend_name: payload.friend_name ?? null,
        challenge_title: payload.challenge_title ?? null,
        challenge_message: payload.challenge_message ?? null,
        friend_media_id: payload.friend_media_id ?? null,
        parent_link_id: payload.parent_link_id ?? null,
    });

export const getQuestions = (sessionUuid) =>
    client.get(`/sessions/${sessionUuid}/questions`);

export const submitAnswers = (sessionUuid, answers) =>
    client.post(`/sessions/${sessionUuid}/answers`, { answers });

export const finalize = (sessionUuid) =>
    client.post(`/sessions/${sessionUuid}/finalize`);
