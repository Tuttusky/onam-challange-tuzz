import client from './client';

export const getByToken = (token) => client.get(`/challenges/${token}`);

export const join = (token, payload) =>
    client.post(`/challenges/${token}/join`, {
        name: payload.name,
        player_uuid: payload.uuid ?? payload.player_uuid ?? null,
    });

export const submitAnswers = (token, sessionUuid, answers) =>
    client.post(`/challenges/${token}/sessions/${sessionUuid}/answers`, { answers });

export const getResults = (token, challengerUuid = null) =>
    client.get(`/challenges/${token}/results`, {
        params: challengerUuid ? { challenger_uuid: challengerUuid } : {},
    });

export const recordShare = (token, payload) =>
    client.post(`/challenges/${token}/shares`, payload);

export const rematch = (token, payload) =>
    client.post(`/challenges/${token}/rematch`, payload);
