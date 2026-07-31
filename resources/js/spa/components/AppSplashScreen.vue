<script setup>
import { ref, onMounted } from 'vue';

const emits = defineEmits(['finish']);
const visible = ref(true);
const fadeOut = ref(false);

onMounted(() => {
    // 2 seconds preload timer
    setTimeout(() => {
        fadeOut.value = true;
        setTimeout(() => {
            visible.value = false;
            emits('finish');
        }, 500); // 500ms fade transition duration
    }, 2000);
});
</script>

<template>
    <Teleport to="body">
        <div v-if="visible" class="splash-screen" :class="{ 'splash-screen--fade': fadeOut }">
            <div class="splash-screen__bg" aria-hidden="true">
                <div class="splash-screen__orb splash-screen__orb--1"></div>
                <div class="splash-screen__orb splash-screen__orb--2"></div>
                <span class="splash-screen__petal splash-screen__petal--1">🌸</span>
                <span class="splash-screen__petal splash-screen__petal--2">🌼</span>
                <span class="splash-screen__petal splash-screen__petal--3">🌺</span>
            </div>

            <div class="splash-screen__content">
                <div class="splash-screen__logo-wrap">
                    <img src="/images/logo.png" alt="Sundarikk Pottuthodal Logo" class="splash-screen__logo" />
                </div>

                <div class="splash-screen__loader">
                    <div class="splash-screen__progress-bar">
                        <div class="splash-screen__progress-fill"></div>
                    </div>
                    <p class="splash-screen__text">Loading Game...</p>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.splash-screen {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #fffaf5 0%, #fef3c7 45%, #fed7aa 100%);
    overflow: hidden;
    transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1), transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.splash-screen--fade {
    opacity: 0;
    transform: scale(1.04);
    pointer-events: none;
}

.splash-screen__bg {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.splash-screen__orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.55;
    animation: orb-pulse 4s ease-in-out infinite alternate;
}

.splash-screen__orb--1 {
    width: 320px;
    height: 320px;
    background: #f43f5e;
    top: -80px;
    right: -60px;
}

.splash-screen__orb--2 {
    width: 280px;
    height: 280px;
    background: #fbbf24;
    bottom: -60px;
    left: -50px;
    animation-delay: -2s;
}

.splash-screen__petal {
    position: absolute;
    font-size: 1.8rem;
    opacity: 0.7;
    animation: float-petal 6s ease-in-out infinite;
}

.splash-screen__petal--1 {
    top: 15%;
    left: 12%;
    animation-delay: 0s;
}

.splash-screen__petal--2 {
    top: 65%;
    right: 15%;
    animation-delay: -2s;
}

.splash-screen__petal--3 {
    bottom: 18%;
    left: 20%;
    animation-delay: -4s;
}

.splash-screen__content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 2rem;
    width: min(440px, 90vw);
}

.splash-screen__logo-wrap {
    animation: logo-entrance 1.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    margin-bottom: 2rem;
}

.splash-screen__logo {
    height: clamp(80px, 24vw, 128px);
    width: auto;
    max-width: 82vw;
    object-fit: contain;
    filter: drop-shadow(0 12px 28px rgba(239, 68, 68, 0.28));
    animation: logo-pulse 2s ease-in-out infinite;
}

.splash-screen__loader {
    width: 180px;
    animation: fade-in-up 0.8s ease 0.3s backwards;
}

.splash-screen__progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(217, 119, 6, 0.18);
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 0.75rem;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
}

.splash-screen__progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #ef4444 0%, #f97316 50%, #eab308 100%);
    border-radius: 999px;
    animation: fill-progress 2s cubic-bezier(0.1, 0.7, 0.1, 1) forwards;
}

.splash-screen__text {
    font-size: 0.88rem;
    font-weight: 800;
    color: #92400e;
    letter-spacing: 0.05em;
    margin: 0;
    text-transform: uppercase;
}

@keyframes logo-entrance {
    0% {
        opacity: 0;
        transform: scale(0.6) translateY(20px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes logo-pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.03);
    }
}

@keyframes fill-progress {
    0% {
        width: 0%;
    }
    100% {
        width: 100%;
    }
}

@keyframes orb-pulse {
    0% {
        transform: scale(1);
        opacity: 0.45;
    }
    100% {
        transform: scale(1.15);
        opacity: 0.65;
    }
}

@keyframes float-petal {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-14px) rotate(12deg);
    }
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
