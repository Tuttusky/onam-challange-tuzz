export function extractChallengeToken(payload) {
    return (
        payload?.challenge?.token ??
        payload?.token ??
        payload?.challenge_link?.token ??
        payload?.challenge_token ??
        null
    );
}

export function buildChallengeUrl(token) {
    return `${window.location.origin}/challenge/${token}`;
}
