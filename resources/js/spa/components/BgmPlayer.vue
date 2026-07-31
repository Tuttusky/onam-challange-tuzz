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

    // Load state preference if saved
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
        // Autoplay blocked by browser policy — wait for user interaction
        isPlaying.value = false;
        addInteractionListeners();
    });
}

function toggleBgm() {
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
        }).catch((e) => console.log('BGM Play Error:', e));
    }
}

function handleUserInteraction() {
    if (userInteracted) return;
    userInteracted = true;
    startBgm();
}

function addInteractionListeners() {
    window.addEventListener('click', handleUserInteraction, { once: true });
    window.addEventListener('touchstart', handleUserInteraction, { once: true });
    window.addEventListener('keydown', handleUserInteraction, { once: true });
    window.addEventListener('pointerdown', handleUserInteraction, { once: true });
}

function removeInteractionListeners() {
    window.removeEventListener('click', handleUserInteraction);
    window.removeEventListener('touchstart', handleUserInteraction);
    window.removeEventListener('keydown', handleUserInteraction);
    window.removeEventListener('pointerdown', handleUserInteraction);
}

onMounted(() => {
    initAudio();
    startBgm();
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
    top: 1.1rem;
    right: 1.1rem;
    z-index: 99999;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1.5px solid rgba(255, 255, 255, 0.8);
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
    transition: transform 0.2s ease, background-color 0.2s ease;
}

.bgm-toggle-btn:hover {
    transform: scale(1.08);
    background: rgba(15, 23, 42, 0.85);
}

.bgm-toggle-btn:active {
    transform: scale(0.92);
}

.bgm-toggle-btn--playing {
    border-color: rgba(245, 158, 11, 0.9);
    background: rgba(180, 83, 9, 0.85);
    box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4);
}

.bgm-waves {
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 16px;
}

.bar {
    width: 3px;
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
    font-size: 1.15rem;
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
