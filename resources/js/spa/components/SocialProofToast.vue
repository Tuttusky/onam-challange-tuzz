<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useSocialProofFeed } from '@/composables/useSocialProofFeed';

const props = defineProps({
    games: {
        type: Array,
        default: () => [],
    },
    paused: {
        type: Boolean,
        default: false,
    },
});

const reducedMotion = ref(false);
let motionQuery = null;

const { current, isVisible, prefersReducedMotion, start, stop, dismiss } = useSocialProofFeed({
    games: computed(() => props.games),
    paused: computed(() => props.paused),
    reducedMotion,
});

const statusClass = computed(() => {
    if (!current.value) {
        return '';
    }

    return `social-proof__pill--${current.value.type}`;
});

function handleMotionChange(event) {
    reducedMotion.value = event.matches;
}

onMounted(() => {
    motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    reducedMotion.value = motionQuery.matches;
    motionQuery.addEventListener('change', handleMotionChange);
    start();
});

onUnmounted(() => {
    if (motionQuery) {
        motionQuery.removeEventListener('change', handleMotionChange);
    }
    stop();
});
</script>

<template>
    <div
        class="social-proof"
        aria-label="Recent player activity"
        :class="{ 'social-proof--reduced': prefersReducedMotion }"
    >
        <Transition :name="prefersReducedMotion ? 'social-proof-fade' : 'social-proof-slide'">
            <article
                v-if="current && isVisible"
                :key="current.id"
                class="social-proof__card"
                role="status"
                aria-live="polite"
            >
                <span
                    class="social-proof__avatar"
                    :style="{ background: current.avatarColor }"
                    aria-hidden="true"
                >
                    {{ current.avatarInitial }}
                </span>

                <div class="social-proof__body">
                    <p class="social-proof__headline">
                        <template v-if="current.type === 'matched'">
                            {{ current.name }} matched
                            <span class="social-proof__count">{{ current.friendCount }}/{{ current.friendTotal }}</span>
                            friends
                        </template>
                        <template v-else-if="current.type === 'playing'">
                            {{ current.name }} is playing with
                            <span class="social-proof__count">{{ current.friendCount }}</span>
                            friends
                        </template>
                        <template v-else>
                            {{ current.headline }}
                        </template>
                    </p>
                    <p class="social-proof__subline">{{ current.subline }}</p>
                </div>

                <span class="social-proof__pill" :class="statusClass">
                    <template v-if="current.type === 'matched' || current.type === 'playing'">
                        {{ current.friendCount }}/{{ current.friendTotal }}
                        <small>Friends</small>
                    </template>
                    <template v-else>
                        {{ current.statusLabel }}
                        <small>{{ current.friendCount }}/{{ current.friendTotal }}</small>
                    </template>
                </span>

                <button
                    type="button"
                    class="social-proof__close"
                    aria-label="Dismiss notification"
                    @click="dismiss"
                >
                    ×
                </button>
            </article>
        </Transition>
    </div>
</template>

<style scoped>
.social-proof {
    --nav-h: 64px;
    --cta-h: 88px;
    --safe-bottom: env(safe-area-inset-bottom, 0px);

    position: fixed;
    left: 50%;
    transform: translateX(-50%);
    bottom: calc(var(--nav-h) + var(--cta-h) + var(--safe-bottom) + 12px);
    z-index: 35;
    width: calc(100% - 2rem);
    max-width: 448px;
    pointer-events: none;
}

.social-proof__card {
    pointer-events: auto;
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.7rem 2rem 0.7rem 0.75rem;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.97);
    border: 1px solid rgba(99, 102, 241, 0.2);
    box-shadow: 0 10px 32px rgba(15, 23, 42, 0.12);
    backdrop-filter: blur(10px);
}

.social-proof__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 800;
    text-transform: uppercase;
}

.social-proof__body {
    flex: 1;
    min-width: 0;
}

.social-proof__headline {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 800;
    color: #1f2937;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.social-proof__count {
    color: #7c3aed;
    font-weight: 900;
}

.social-proof__subline {
    margin: 0.12rem 0 0;
    font-size: 0.7rem;
    font-weight: 600;
    color: #6b7280;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.social-proof__pill {
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.05rem;
    padding: 0.28rem 0.5rem;
    border-radius: 10px;
    font-size: 0.72rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: 0.01em;
}

.social-proof__pill small {
    font-size: 0.55rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    opacity: 0.85;
}

.social-proof__pill--playing {
    background: #e0e7ff;
    color: #ea580c;
}

.social-proof__pill--won {
    background: #dcfce7;
    color: #16a34a;
}

.social-proof__pill--lost {
    background: #fee2e2;
    color: #dc2626;
}

.social-proof__pill--matched {
    background: #ede9fe;
    color: #7c3aed;
}

.social-proof__close {
    position: absolute;
    top: 0.35rem;
    right: 0.35rem;
    width: 22px;
    height: 22px;
    border: 0;
    border-radius: 50%;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 1rem;
    line-height: 1;
    display: grid;
    place-items: center;
    cursor: pointer;
    font-family: inherit;
    padding: 0;
}

.social-proof__card {
    position: relative;
}

.social-proof-slide-enter-active,
.social-proof-slide-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.social-proof-slide-enter-from,
.social-proof-slide-leave-to {
    opacity: 0;
    transform: translateY(16px);
}

.social-proof-fade-enter-active,
.social-proof-fade-leave-active {
    transition: opacity 0.2s ease;
}

.social-proof-fade-enter-from,
.social-proof-fade-leave-to {
    opacity: 0;
}

.social-proof--reduced .social-proof-slide-enter-active,
.social-proof--reduced .social-proof-slide-leave-active {
    transition: opacity 0.2s ease;
}

.social-proof--reduced .social-proof-slide-enter-from,
.social-proof--reduced .social-proof-slide-leave-to {
    transform: none;
}

@media (prefers-reduced-motion: reduce) {
    .social-proof-slide-enter-active,
    .social-proof-slide-leave-active {
        transition: opacity 0.2s ease;
    }

    .social-proof-slide-enter-from,
    .social-proof-slide-leave-to {
        transform: none;
    }
}
</style>
