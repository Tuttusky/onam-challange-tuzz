<script setup>
import { computed } from 'vue';

const props = defineProps({
    progress: {
        type: Number,
        default: 0,
    },
    size: {
        type: Number,
        default: 72,
    },
    stroke: {
        type: Number,
        default: 6,
    },
});

const radius = computed(() => (props.size - props.stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(() => circumference.value - (props.progress / 100) * circumference.value);
</script>

<template>
    <div class="progress-ring" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
            <circle
                class="progress-ring__track"
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                :stroke-width="stroke"
                fill="none"
            />
            <circle
                class="progress-ring__fill"
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                :stroke-width="stroke"
                fill="none"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
            />
        </svg>
        <div class="progress-ring__label">
            <slot>{{ Math.round(progress) }}%</slot>
        </div>
    </div>
</template>

<style scoped>
.progress-ring {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.progress-ring svg {
    transform: rotate(-90deg);
}

.progress-ring__track {
    stroke: rgba(255, 255, 255, 0.2);
}

.progress-ring__fill {
    stroke: var(--primary);
    stroke-linecap: round;
    transition: stroke-dashoffset 0.45s ease;
    filter: drop-shadow(0 0 6px rgba(99, 102, 241, 0.5));
}

.progress-ring__label {
    position: absolute;
    font-size: 0.85rem;
    font-weight: 700;
    color: #fff;
}
</style>
