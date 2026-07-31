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
        }).catch((err) => console.log('BGM Play Error:', err));
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
            :class="{ 'bgm-toggle-btn--playing': isPlaying, 'bgm-toggle-btn--muted': isMuted || !isPlaying }"
            :aria-label="isPlaying ? 'Pause Background Music' : 'Play Background Music'"
            @click="toggleBgm"
            @touchstart.stop="toggleBgm"
        >
            <span v-if="isPlaying && !isMuted" class="bgm-waves" aria-hidden="true">
                <span class="bar bar--1"></span>
                <span class="bar bar--2"></span>
                <span class="bar bar--3"></span>
            </span>
            <span v-else class="bgm-icon" aria-hidden="true">🔇</span>
        </button>
    </Teleport>
</template>

<style scoped>
.bgm-toggle-btn {
    position: fixed;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 99999;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.75);
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    transition: transform 0.15s ease, background-color 0.15s ease;
    touch-action: manipulation;
}

.bgm-toggle-btn:hover {
    transform: scale(1.06);
    background: rgba(15, 23, 42, 0.85);
}

.bgm-toggle-btn:active {
    transform: scale(0.92);
}

.bgm-toggle-btn--playing {
    border-color: rgba(245, 158, 11, 0.9);
    background: rgba(180, 83, 9, 0.85);
    box-shadow: 0 3px 12px rgba(245, 158, 11, 0.35);
}

.bgm-waves {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    height: 13px;
}

.bar {
    width: 2.2px;
    background: #ffffff;
    border-radius: 99px;
    animation: sound-wave 1s ease-in-out infinite alternate;
}

.bar--1 {
    height: 60%;
    animation-delay: 0s;
}

.bar--2 {
    height: 100%;
    animation-delay: -0.3s;
}

.bar--3 {
    height: 45%;
    animation-delay: -0.6s;
}

.bgm-icon {
    font-size: 0.85rem;
    line-height: 1;
}

@keyframes sound-wave {
    0% {
        height: 30%;
    }
    100% {
        height: 100%;
    }
}
</style>
