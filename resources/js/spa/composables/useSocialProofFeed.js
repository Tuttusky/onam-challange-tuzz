import { ref, computed, watch, onUnmounted, unref } from 'vue';

const NAMES = [
    'Anjali', 'Goutham', 'Meera', 'Arjun', 'Lakshmi', 'Rahul', 'Divya', 'Vishnu',
    'Priya', 'Aditya', 'Sneha', 'Kiran', 'Asha', 'Nikhil', 'Deepa', 'Rohan',
    'Keerthi', 'Manu', 'Reshma', 'Sajan',
];

const LOCATIONS = [
    'Kochi', 'Thiruvananthapuram', 'Kozhikode', 'Thrissur', 'Kottayam',
    'Palakkad', 'Alappuzha', 'Kannur', 'Malappuram', 'Kollam',
];

const AVATAR_COLORS = [
    'linear-gradient(135deg, #818cf8, #6366f1)',
    'linear-gradient(135deg, #a78bfa, #60a5fa)',
    'linear-gradient(135deg, #34d399, #38bdf8)',
    'linear-gradient(135deg, #c084fc, #818cf8)',
    'linear-gradient(135deg, #818cf8, #c084fc)',
    'linear-gradient(135deg, #2dd4bf, #38bdf8)',
];

const EVENT_WEIGHTS = [
    { type: 'playing', weight: 35 },
    { type: 'won', weight: 25 },
    { type: 'lost', weight: 20 },
    { type: 'matched', weight: 20 },
];

const DEFAULT_GAMES = [
    { name: 'Sundarikk Pottu Thodal', slug: 'sundarikk-pottu-thodal' },
    { name: 'Onam Dare Challenge', slug: 'onam-dare-challenge' },
    { name: 'Spin & Win', slug: 'spin-win' },
];

function pickRandom(items) {
    return items[Math.floor(Math.random() * items.length)];
}

function pickWeighted(weights) {
    const total = weights.reduce((sum, item) => sum + item.weight, 0);
    let roll = Math.random() * total;

    for (const item of weights) {
        roll -= item.weight;
        if (roll <= 0) {
            return item.type;
        }
    }

    return weights[0].type;
}

function pickOtherName(exclude) {
    const pool = NAMES.filter((name) => name !== exclude);
    return pickRandom(pool.length ? pool : NAMES);
}

function avatarColorFor(name) {
    let hash = 0;
    for (let i = 0; i < name.length; i += 1) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}

function resolveGames(games) {
    const list = unref(games);
    if (!Array.isArray(list) || list.length === 0) {
        return DEFAULT_GAMES;
    }

    return list.map((game) => ({
        name: game.name,
        slug: game.slug,
    }));
}

function buildNotification(games) {
    const name = pickRandom(NAMES);
    const location = pickRandom(LOCATIONS);
    const game = pickRandom(resolveGames(games));
    const type = pickWeighted(EVENT_WEIGHTS);
    const friend = pickOtherName(name);
    const friendCount = Math.floor(Math.random() * 14) + 1;
    const friendTotal = 15;
    const friendsLabel = `${friendCount}/${friendTotal} Friends`;

    const base = {
        id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        type,
        name,
        location,
        game: game.name,
        friendCount,
        friendTotal,
        friendsLabel,
        avatarInitial: name.charAt(0).toUpperCase(),
        avatarColor: avatarColorFor(name),
    };

    switch (type) {
        case 'playing':
            return {
                ...base,
                headline: `${name} is playing with ${friendCount} friends`,
                subline: `${location} · ${game.name}`,
                statusLabel: friendsLabel,
            };
        case 'won':
            return {
                ...base,
                headline: `${name} won against ${friend}`,
                subline: `${location} · matched ${friendsLabel} on ${game.name}`,
                statusLabel: 'Won',
                friend,
            };
        case 'lost':
            return {
                ...base,
                headline: `${name} lost to ${friend}`,
                subline: `${location} · matched ${friendsLabel} on ${game.name}`,
                statusLabel: 'Lost',
                friend,
            };
        case 'matched':
        default:
            return {
                ...base,
                headline: `${name} matched ${friendCount} of ${friendTotal} friends`,
                subline: `${location} · ${game.name}`,
                statusLabel: friendsLabel,
            };
    }
}

export function useSocialProofFeed(options = {}) {
    const { games, paused, reducedMotion } = options;

    const current = ref(null);
    const isVisible = ref(false);

    let showTimer = null;
    let hideTimer = null;
    let gapTimer = null;
    let started = false;

    const isPaused = computed(() => Boolean(unref(paused)));
    const prefersReducedMotion = computed(() => Boolean(unref(reducedMotion)));

    function clearTimers() {
        [showTimer, hideTimer, gapTimer].forEach((timer) => {
            if (timer) {
                clearTimeout(timer);
            }
        });
        showTimer = null;
        hideTimer = null;
        gapTimer = null;
    }

    function randomGap() {
        if (prefersReducedMotion.value) {
            return 8000 + Math.random() * 4000;
        }
        return 3000 + Math.random() * 3000;
    }

    function showDuration() {
        return prefersReducedMotion.value ? 6000 : 4500;
    }

    function initialDelay() {
        return prefersReducedMotion.value ? 3000 : 2000;
    }

    function scheduleNext() {
        if (!started || isPaused.value) {
            return;
        }

        gapTimer = setTimeout(() => {
            showNext();
        }, randomGap());
    }

    function hideCurrent() {
        isVisible.value = false;
        hideTimer = setTimeout(() => {
            current.value = null;
            scheduleNext();
        }, prefersReducedMotion.value ? 200 : 300);
    }

    function showNext() {
        if (!started || isPaused.value) {
            return;
        }

        current.value = buildNotification(games);
        isVisible.value = true;

        hideTimer = setTimeout(() => {
            hideCurrent();
        }, showDuration());
    }

    function start() {
        if (started) {
            return;
        }

        started = true;
        showTimer = setTimeout(() => {
            showNext();
        }, initialDelay());
    }

    function stop() {
        started = false;
        clearTimers();
        isVisible.value = false;
        current.value = null;
    }

    function dismiss() {
        if (!current.value) {
            return;
        }

        if (hideTimer) {
            clearTimeout(hideTimer);
        }

        hideCurrent();
    }

    watch(isPaused, (value) => {
        if (value) {
            clearTimers();
            isVisible.value = false;
            current.value = null;
            return;
        }

        if (started) {
            showTimer = setTimeout(() => {
                showNext();
            }, prefersReducedMotion.value ? 1500 : 800);
        }
    });

    onUnmounted(() => {
        stop();
    });

    return {
        current,
        isVisible,
        prefersReducedMotion,
        start,
        stop,
        dismiss,
    };
}
