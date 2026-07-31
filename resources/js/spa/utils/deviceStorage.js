/**
 * Cookie and LocalStorage Device Storage Utility
 * Persists creator and player names, challenge histories, and fast game caches in device cookies & local storage.
 */

export function setCookie(name, value, days = 365) {
    if (typeof document === 'undefined') return;
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
}

export function getCookie(name) {
    if (typeof document === 'undefined') return null;
    return document.cookie.split('; ').reduce((r, v) => {
        const parts = v.split('=');
        return parts[0] === name ? decodeURIComponent(parts[1]) : r;
    }, null);
}

export function eraseCookie(name) {
    setCookie(name, '', -1);
}

// Keys
const KEYS = {
    PLAYER_NAME: 'onam_player_name',
    CREATOR_NAME: 'onam_creator_name',
    PLAYER_UUID: 'player_uuid',
    LAST_CHALLENGE_TOKEN: 'onam_last_challenge_token',
    LAST_SHARE_URL: 'onam_last_share_url',
    CREATED_HISTORY: 'onam_created_challenges',
    PLAYED_HISTORY: 'onam_played_challenges',
    CACHED_CONFIG: 'cached_pottu_config',
};

export const deviceStorage = {
    // Save Player / Creator Name in both Cookie & LocalStorage
    savePlayerName(name) {
        if (!name) return;
        const trimmed = name.trim();
        setCookie(KEYS.PLAYER_NAME, trimmed, 365);
        localStorage.setItem(KEYS.PLAYER_NAME, trimmed);
        localStorage.setItem('player_name', trimmed);
    },

    saveCreatorName(name) {
        if (!name) return;
        const trimmed = name.trim();
        setCookie(KEYS.CREATOR_NAME, trimmed, 365);
        localStorage.setItem(KEYS.CREATOR_NAME, trimmed);
    },

    getPlayerName() {
        return (
            localStorage.getItem(KEYS.PLAYER_NAME) ||
            localStorage.getItem('player_name') ||
            getCookie(KEYS.PLAYER_NAME) ||
            localStorage.getItem(KEYS.CREATOR_NAME) ||
            getCookie(KEYS.CREATOR_NAME) ||
            ''
        );
    },

    getCreatorName() {
        return (
            localStorage.getItem(KEYS.CREATOR_NAME) ||
            getCookie(KEYS.CREATOR_NAME) ||
            localStorage.getItem(KEYS.PLAYER_NAME) ||
            getCookie(KEYS.PLAYER_NAME) ||
            ''
        );
    },

    // Save Created Challenge details
    saveCreatedChallenge(token, shareUrl, details = {}) {
        if (!token) return;
        setCookie(KEYS.LAST_CHALLENGE_TOKEN, token, 180);
        localStorage.setItem(KEYS.LAST_CHALLENGE_TOKEN, token);
        if (shareUrl) {
            setCookie(KEYS.LAST_SHARE_URL, shareUrl, 180);
            localStorage.setItem(KEYS.LAST_SHARE_URL, shareUrl);
        }

        try {
            const raw = localStorage.getItem(KEYS.CREATED_HISTORY);
            const history = raw ? JSON.parse(raw) : [];
            const updated = [
                { token, shareUrl, date: new Date().toISOString(), ...details },
                ...history.filter((h) => h.token !== token),
            ].slice(0, 20); // Keep last 20
            localStorage.setItem(KEYS.CREATED_HISTORY, JSON.stringify(updated));
        } catch (e) {
            console.warn('Failed to update created history:', e);
        }
    },

    getLastCreatedChallengeToken() {
        return localStorage.getItem(KEYS.LAST_CHALLENGE_TOKEN) || getCookie(KEYS.LAST_CHALLENGE_TOKEN) || null;
    },

    getLastShareUrl() {
        return localStorage.getItem(KEYS.LAST_SHARE_URL) || getCookie(KEYS.LAST_SHARE_URL) || null;
    },

    // Save Played Challenge details (for acceptors)
    savePlayedChallenge(challengeToken, resultData = {}) {
        if (!challengeToken) return;
        try {
            const raw = localStorage.getItem(KEYS.PLAYED_HISTORY);
            const history = raw ? JSON.parse(raw) : [];
            const updated = [
                { token: challengeToken, result: resultData, date: new Date().toISOString() },
                ...history.filter((h) => h.token !== challengeToken),
            ].slice(0, 20);
            localStorage.setItem(KEYS.PLAYED_HISTORY, JSON.stringify(updated));
        } catch (e) {
            console.warn('Failed to update played history:', e);
        }
    },

    // Fast Cache for Instant Game Loading
    saveGameConfigCache(config) {
        try {
            localStorage.setItem(KEYS.CACHED_CONFIG, JSON.stringify({
                data: config,
                timestamp: Date.now()
            }));
        } catch (e) {
            console.warn('Failed to cache game config:', e);
        }
    },

    getGameConfigCache() {
        try {
            const raw = localStorage.getItem(KEYS.CACHED_CONFIG);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            // 24 hour max cache age
            if (Date.now() - parsed.timestamp < 86400000) {
                return parsed.data;
            }
        } catch (e) {
            return null;
        }
        return null;
    }
};
