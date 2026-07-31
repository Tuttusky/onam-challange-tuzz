<script setup>
import { ref, computed, onMounted } from 'vue';
import gsap from 'gsap';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';

const FRIEND_COLORS = [
    '#dc2626',
    '#6366f1',
    '#eab308',
    '#3b82f6',
    '#a855f7',
    '#8b5cf6',
    '#14b8a6',
    '#6366f1',
];

const props = defineProps({
    creatorPosition: { type: Object, required: true },
    friendPosition: { type: Object, default: null },
    friends: { type: Array, default: () => [] },
    imageUrl: { type: String, default: '' },
    referenceSize: { type: Object, default: () => ({ width: 400, height: 600 }) },
    pixelDistance: { type: Number, default: 0 },
    label: { type: String, default: '' },
    creatorName: { type: String, default: 'Creator' },
    friendName: { type: String, default: 'Friend' },
    topWinner: { type: Object, default: null },
});

const boardRef = ref(null);
const { t } = usePottuFlowI18n();

const friendEntries = computed(() => {
    if (props.friends?.length) {
        return props.friends.map((friend, index) => ({
            ...friend,
            color: FRIEND_COLORS[index % FRIEND_COLORS.length],
        }));
    }

    if (props.friendPosition) {
        return [{
            name: props.friendName,
            position: props.friendPosition,
            accuracy: null,
            pixel_distance: props.pixelDistance,
            label: props.label,
            color: FRIEND_COLORS[0],
            is_current: true,
        }];
    }

    return [];
});

function dotStyle(position, color = '#dc2626') {
    return {
        left: `${position.x * 100}%`,
        top: `${position.y * 100}%`,
        background: color,
        '--dot-color': color,
    };
}

onMounted(() => {
    if (boardRef.value) {
        gsap.fromTo(boardRef.value, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.45, ease: 'power2.out' });
    }
});
</script>

<template>
    <div ref="boardRef" class="result-comparison glass-card p-3">
        <div class="result-comparison__header text-center mb-3">
            <p class="text-white-50 small mb-1">{{ t('creators_challenge', { name: creatorName }) }}</p>
            <h3 class="text-white h5 mb-1">{{ label || t('all_guesses') }}</h3>
            <p v-if="topWinner" class="result-comparison__top-winner mb-0">
                {{ t('top_winner') }}: <strong>{{ topWinner.name || friendName }}</strong>
                <span v-if="topWinner.accuracy != null"> — {{ Math.round(topWinner.accuracy) }}% accuracy</span>
            </p>
        </div>

        <div class="result-comparison__board">
            <img
                v-if="imageUrl"
                :src="imageUrl"
                alt="Challenge reveal"
                class="result-comparison__image"
                draggable="false"
            />

            <div
                class="result-dot result-dot--creator"
                :style="dotStyle(creatorPosition, '#22c55e')"
            >
                <span class="result-dot__label result-dot__label--creator">{{ creatorName }}</span>
            </div>

            <div
                v-for="(friend, index) in friendEntries"
                :key="friend.uuid || friend.session_uuid || index"
                class="result-dot result-dot--friend"
                :class="{
                    'result-dot--current': friend.is_current,
                    'result-dot--top': topWinner?.uuid && friend.uuid === topWinner.uuid,
                }"
                :style="dotStyle(friend.position, friend.color)"
            >
                <span class="result-dot__label" :style="{ borderColor: friend.color }">
                    {{ friend.name || friendName }}
                    <template v-if="friend.accuracy != null"> · {{ Math.round(friend.accuracy) }}%</template>
                </span>
            </div>
        </div>

        <ul v-if="friendEntries.length" class="result-friends-list list-unstyled mb-0 mt-3">
            <li
                v-for="(friend, index) in friendEntries"
                :key="`row-${friend.uuid || index}`"
                class="result-friends-list__item"
                :class="{
                    'result-friends-list__item--current': friend.is_current,
                    'result-friends-list__item--top': topWinner?.uuid && friend.uuid === topWinner.uuid,
                }"
            >
                <span class="result-friends-list__dot" :style="{ background: friend.color }"></span>
                <div class="result-friends-list__info">
                    <strong>{{ friend.name || friendName }}</strong>
                    <small v-if="friend.label" class="text-white-50">{{ friend.label }}</small>
                </div>
                <div class="result-friends-list__stats text-end">
                    <span v-if="friend.accuracy != null" class="result-friends-list__accuracy">
                        {{ Math.round(friend.accuracy) }}%
                    </span>
                    <small v-if="friend.pixel_distance != null" class="text-white-50 d-block">
                        {{ Math.round(friend.pixel_distance) }} px
                    </small>
                </div>
                <span v-if="topWinner?.uuid && friend.uuid === topWinner.uuid" class="result-friends-list__badge">
                    {{ t('winner_badge') }}
                </span>
            </li>
        </ul>

        <div class="result-legend d-flex justify-content-center flex-wrap gap-3 mt-3">
            <span><i class="legend-dot legend-dot--creator"></i> {{ creatorName }}</span>
            <span v-for="(friend, index) in friendEntries" :key="`legend-${index}`">
                <i class="legend-dot" :style="{ background: friend.color }"></i> {{ friend.name || friendName }}
            </span>
        </div>
    </div>
</template>

<style scoped>
.result-comparison__top-winner {
    color: #fde68a;
    font-size: 0.9rem;
}

.result-comparison__board {
    position: relative;
    width: 100%;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.08);
    overflow: hidden;
    line-height: 0;
}

.result-comparison__image {
    display: block;
    width: 100%;
    height: auto;
    pointer-events: none;
}

.result-dot {
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    border: 3px solid rgba(255, 255, 255, 0.95);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
}

.result-dot--creator {
    z-index: 3;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.4), 0 2px 10px rgba(34, 197, 94, 0.45);
}

.result-dot--current {
    z-index: 4;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.9), 0 0 0 6px rgba(129, 140, 248, 0.45);
}

.result-dot--top {
    z-index: 5;
}

.result-dot__label {
    position: absolute;
    top: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.78);
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    line-height: 1.2;
    border: 1px solid rgba(255, 255, 255, 0.15);
    pointer-events: none;
}

.result-dot__label--creator {
    border-color: rgba(34, 197, 94, 0.5);
}

.result-friends-list__item {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 0.75rem;
    margin-bottom: 0.45rem;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid transparent;
}

.result-friends-list__item--current {
    border-color: rgba(129, 140, 248, 0.35);
    background: rgba(129, 140, 248, 0.08);
}

.result-friends-list__item--top {
    border-color: rgba(34, 197, 94, 0.4);
    background: rgba(34, 197, 94, 0.1);
}

.result-friends-list__dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.result-friends-list__info {
    color: #fff;
    min-width: 0;
}

.result-friends-list__info strong {
    display: block;
    font-size: 0.9rem;
}

.result-friends-list__accuracy {
    color: var(--accent, #818cf8);
    font-weight: 800;
    font-size: 1rem;
}

.result-friends-list__badge {
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    background: rgba(34, 197, 94, 0.2);
    color: #86efac;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
}

.result-legend {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.85rem;
}

.legend-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 0.35rem;
    vertical-align: middle;
}

.legend-dot--creator {
    background: #22c55e;
}
</style>
