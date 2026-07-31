<script setup>
import { ref, onMounted } from 'vue';
import { useAnimation } from '@/composables/useAnimation';

defineProps({
    showFooter: {
        type: Boolean,
        default: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    dark: {
        type: Boolean,
        default: false,
    },
    cream: {
        type: Boolean,
        default: false,
    },
});

const layoutRef = ref(null);
const { fadeIn } = useAnimation();

onMounted(() => {
    fadeIn(layoutRef.value);
});
</script>

<template>
    <div
        ref="layoutRef"
        class="glass-layout"
        :class="{
            'glass-layout--compact': compact,
            'glass-layout--dark': dark,
            'glass-layout--cream': cream,
        }"
    >
        <div class="glass-layout__bg" aria-hidden="true">
            <div class="glass-layout__orb glass-layout__orb--1"></div>
            <div class="glass-layout__orb glass-layout__orb--2"></div>
            <div class="glass-layout__orb glass-layout__orb--3"></div>
        </div>

        <div class="glass-layout__content container-fluid px-3 px-md-4">
            <header v-if="$slots.header" class="glass-layout__header text-center mb-4">
                <slot name="header" />
            </header>

            <main class="glass-layout__main">
                <slot />
            </main>

            <footer v-if="showFooter && $slots.footer" class="glass-layout__footer text-center mt-4">
                <slot name="footer" />
            </footer>
        </div>
    </div>
</template>

<style scoped>
.glass-layout {
    min-height: 100dvh;
    position: relative;
    overflow: hidden;
    padding: 1.25rem 0 2rem;
}

.glass-layout--compact {
    padding-top: 0.75rem;
}

.glass-layout__bg {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.glass-layout__orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.45;
    animation: float 8s ease-in-out infinite;
}

.glass-layout__orb--1 {
    width: 280px;
    height: 280px;
    background: var(--primary);
    top: -60px;
    right: -40px;
}

.glass-layout__orb--2 {
    width: 220px;
    height: 220px;
    background: var(--accent, #818cf8);
    bottom: 10%;
    left: -50px;
    animation-delay: -2s;
}

.glass-layout__orb--3 {
    width: 180px;
    height: 180px;
    background: var(--secondary);
    top: 40%;
    right: 10%;
    animation-delay: -4s;
}

.glass-layout__content {
    position: relative;
    z-index: 1;
    max-width: 640px;
    margin: 0 auto;
}

.glass-layout__main {
    animation: fade-in 0.6s ease-out;
}

.glass-layout--dark {
    background: #0a0e17;
}

.glass-layout--dark .glass-layout__bg {
    background: linear-gradient(165deg, #0a0e17 0%, #121826 45%, #0d111c 100%);
}

.glass-layout--dark .glass-layout__orb {
    opacity: 0.12;
    filter: blur(80px);
}

.glass-layout--dark .glass-layout__orb--1 {
    background: #1e3a5f;
}

.glass-layout--dark .glass-layout__orb--2 {
    background: #312e81;
}

.glass-layout--dark .glass-layout__orb--3 {
    background: #14532d;
}

.glass-layout--dark :deep(.glass-card) {
    background: rgba(15, 23, 42, 0.82);
    border-color: rgba(255, 255, 255, 0.08);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
}

.glass-layout--cream.glass-layout {
    background: #fffaf5;
    color: #1f2937;
    overflow-x: hidden;
    overflow-y: auto;
}

.glass-layout--cream .glass-layout__bg {
    background: #fffaf5;
}

.glass-layout--cream .glass-layout__orb {
    opacity: 0.6;
    filter: blur(70px);
}

.glass-layout--cream .glass-layout__orb--1 {
    background: #e0e7ff;
    top: -60px;
    right: -40px;
}

.glass-layout--cream .glass-layout__orb--2 {
    background: #fce7f3;
    bottom: 10%;
    left: -50px;
}

.glass-layout--cream .glass-layout__orb--3 {
    display: none;
}

.glass-layout--cream :deep(.glass-card) {
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(229, 231, 235, 0.9);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    color: #1f2937;
}

.glass-layout--cream :deep(.btn-glass) {
    background: #ffffff;
    border: 1.5px solid #c7d2fe;
    color: #4338ca;
}

.glass-layout--cream :deep(.btn-glass:hover),
.glass-layout--cream :deep(.btn-glass:focus) {
    background: #eef2ff;
    color: #4338ca;
}

.glass-layout--cream :deep(.text-white) {
    color: #1f2937 !important;
}

.glass-layout--cream :deep(.text-white-50) {
    color: #6b7280 !important;
}

.glass-layout--cream :deep(.btn-primary-glass) {
    background: linear-gradient(90deg, var(--primary), var(--accent));
    border: none;
    color: #ffffff !important;
    box-shadow: 0 10px 24px rgba(236, 72, 153, 0.25);
}

.glass-layout--cream :deep(.btn-primary-glass:hover) {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(236, 72, 153, 0.4);
}

.glass-layout--cream :deep(.play-progress) {
    background: rgba(0, 0, 0, 0.08);
}
</style>
