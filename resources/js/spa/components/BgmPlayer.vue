<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const isPlaying = ref(false);
const isMuted = ref(false);
let bgmAudio = null;
let userInteracted = false;

function initAudio() {
    if (bgmAudio) return;

    bgmAudio = new Audio('/audio/bgm.mp3');
    bgmAudio.loop = true;
    bgmAudio.volume = 0.45;

    const savedMuted = localStorage.getItem('bgm_muted');
    if (savedMuted === 'true') {
        isMuted.value = true;
        bgmAudio.muted = true;
    }
}

function startBgm() {
    if (!bgmAudio) initAudio();
    if (isMuted.value) return;

    bgmAudio.play().then(() => {
        isPlaying.value = true;
        removeInteractionListeners();
    }).catch(() => {
        isPlaying.value = false;
        addInteractionListeners();
    });
}

function toggleBgm(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    initAudio();

    if (isPlaying.value && !bgmAudio.paused) {
        bgmAudio.pause();
        isPlaying.value = false;
        isMuted.value = true;
        localStorage.setItem('bgm_muted', 'true');
    } else {
        isMuted.value = false;
        bgmAudio.muted = false;
        localStorage.setItem('bgm_muted', 'false');
        bgmAudio.play().then(() => {
            isPlaying.value = true;
        }).catch((err) => {
            console.log('BGM Play Error:', err);
            isPlaying.value = false;
        });
    }
}

function handleUserInteraction() {
    if (userInteracted) return;
    userInteracted = true;
    startBgm();
}

function addInteractionListeners() {
    const opts = { capture: true, passive: true, once: true };
    window.addEventListener('touchstart', handleUserInteraction, opts);
    window.addEventListener('touchend', handleUserInteraction, opts);
    window.addEventListener('pointerdown', handleUserInteraction, opts);
    window.addEventListener('click', handleUserInteraction, opts);
    window.addEventListener('keydown', handleUserInteraction, opts);
    document.addEventListener('touchstart', handleUserInteraction, opts);
    document.addEventListener('click', handleUserInteraction, opts);
}

function removeInteractionListeners() {
    window.removeEventListener('touchstart', handleUserInteraction);
    window.removeEventListener('touchend', handleUserInteraction);
    window.removeEventListener('pointerdown', handleUserInteraction);
    window.removeEventListener('click', handleUserInteraction);
    window.removeEventListener('keydown', handleUserInteraction);
    document.removeEventListener('touchstart', handleUserInteraction);
    document.removeEventListener('click', handleUserInteraction);
}

onMounted(() => {
    initAudio();
    startBgm();
    addInteractionListeners();
});

onUnmounted(() => {
    removeInteractionListeners();
    if (bgmAudio) {
        bgmAudio.pause();
        bgmAudio = null;
    }
});
</script>

<template>
    <Teleport to="body">
        <button
            type="button"
            class="bgm-toggle-btn"
            :class="{ 'bgm-toggle-btn--playing': isPlaying && !isMuted, 'bgm-toggle-btn--muted': isMuted || !isPlaying }"
            :aria-label="isPlaying && !isMuted ? 'Pause Background Music' : 'Play Background Music'"
            @click.stop.prevent="toggleBgm"
        >
            <!-- Speaker ON Icon (Playing) -->
            <svg v-if="isPlaying && !isMuted" class="speaker-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                <path d="M15.54 8.46a5 5 0 0 1 0 7.07" class="speaker-wave-1"></path>
                <path d="M19.07 4.93a10 10 0 0 1 0 14.14" class="speaker-wave-2"></path>
            </svg>

            <!-- Speaker OFF / Muted Icon -->
            <svg v-else class="speaker-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="1" y1="1" x2="23" y2="23"></line>
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            </svg>
        </button>
    </Teleport>
</template>

<style scoped>
.bgm-toggle-btn {
    position: fixed;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 99999;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1.5px solid rgba(255, 255, 255, 0.85);
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
    transition: transform 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
    touch-action: manipulation;
}

.bgm-toggle-btn:hover {
    transform: scale(1.08);
    background: rgba(15, 23, 42, 0.88);
}

.bgm-toggle-btn:active {
    transform: scale(0.92);
}

.bgm-toggle-btn--playing {
    border-color: #f59e0b;
    background: linear-gradient(135deg, #d97706, #b45309);
    box-shadow: 0 4px 14px rgba(217, 119, 6, 0.45);
}

.bgm-toggle-btn--muted {
    border-color: rgba(255, 255, 255, 0.6);
    background: rgba(15, 23, 42, 0.7);
}

.speaker-icon {
    display: block;
    width: 16px;
    height: 16px;
}

.speaker-wave-1 {
    animation: wave-pulse 1.2s ease-in-out infinite alternate;
}

.speaker-wave-2 {
    animation: wave-pulse 1.2s ease-in-out infinite alternate 0.3s;
}

@keyframes wave-pulse {
    0% {
        opacity: 0.3;
    }
    100% {
        opacity: 1;
    }
}
</style>
