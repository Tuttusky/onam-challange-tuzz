<script setup>
import { useUiStore } from '@/stores/ui';

const uiStore = useUiStore();
</script>

<template>
    <div class="loading-overlay" role="status" aria-live="polite">
        <div class="loading-overlay__content">
            <div class="loading-overlay__spinner"></div>
            <p class="loading-overlay__text">Loading...</p>
            <p v-if="uiStore.toast" class="loading-overlay__toast">{{ uiStore.toast.message }}</p>
        </div>
    </div>
</template>

<style scoped>
.loading-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(10, 10, 20, 0.55);
    backdrop-filter: blur(6px);
}

.loading-overlay__content {
    text-align: center;
}

.loading-overlay__spinner {
    width: 52px;
    height: 52px;
    border: 4px solid rgba(255, 255, 255, 0.2);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 1rem;
}

.loading-overlay__text {
    color: #fff;
    font-weight: 600;
    margin: 0;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
