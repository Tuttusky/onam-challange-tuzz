<script setup>
import { computed } from 'vue';

const props = defineProps({
    enabled: { type: Boolean, default: true },
    color: { type: String, default: '#FFFFFF' },
    opacity: { type: Number, default: 1 },
    hint: { type: String, default: 'Tap once to guess!' },
});

const overlayStyle = computed(() => ({
    backgroundColor: props.color,
    opacity: props.enabled ? props.opacity : 0,
}));
</script>

<template>
    <div v-if="enabled" class="reveal-overlay" :style="overlayStyle">
        <span class="reveal-hint">{{ hint }}</span>
    </div>
</template>

<style scoped>
.reveal-overlay {
    position: absolute;
    inset: 0;
    z-index: 3;
    display: grid;
    place-items: center;
    pointer-events: none;
}

.reveal-hint {
    color: rgba(0, 0, 0, 0.5);
    font-size: 0.9rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    pointer-events: none;
}
</style>
