<script setup>
const props = defineProps({
    options: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Object,
        default: null,
    },
    feedback: {
        type: String,
        default: null,
    },
    creatorAnswer: {
        type: Object,
        default: null,
    },
    locked: {
        type: Boolean,
        default: false,
    },
    shuffleAnimated: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select']);

function pick(option) {
    if (props.locked) {
        return;
    }

    emit('select', option);
}

function isActive(option) {
    return props.modelValue?.optionId === option.id;
}

function isCreatorOption(option) {
    return (
        props.creatorAnswer?.optionId != null &&
        Number(props.creatorAnswer.optionId) === Number(option.id)
    );
}

function itemClass(option) {
    const selected = isActive(option);

    if (props.feedback && selected) {
        return {
            'emoji-picker__item--correct': props.feedback === 'correct',
            'emoji-picker__item--wrong': props.feedback === 'wrong',
        };
    }

    if (props.feedback === 'wrong' && isCreatorOption(option) && !selected) {
        return { 'emoji-picker__item--correct': true };
    }

    return { 'emoji-picker__item--active': selected };
}
</script>

<template>
    <div class="emoji-picker">
        <button
            v-for="(option, index) in options"
            :key="option.id"
            type="button"
            class="emoji-picker__item"
            :class="[itemClass(option), { 'emoji-picker__item--shuffle-in': shuffleAnimated }]"
            :style="shuffleAnimated ? { animationDelay: `${index * 0.07}s` } : undefined"
            :disabled="locked"
            @click="pick(option)"
        >
            <span class="emoji-picker__icon">{{ option.icon || option.label }}</span>
            <span class="emoji-picker__label">{{ option.label }}</span>
        </button>
    </div>
</template>

<style scoped>
.emoji-picker {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.emoji-picker__item {
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
    border-radius: 1rem;
    padding: 1rem 0.75rem;
    color: #fff;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.emoji-picker__item:hover:not(:disabled) {
    transform: translateY(-2px);
}

.emoji-picker__item--active {
    border-color: var(--accent, #818cf8);
    box-shadow: 0 0 20px rgba(129, 140, 248, 0.25);
}

.emoji-picker__item--correct {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.28);
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.5);
}

.emoji-picker__item--wrong {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.28);
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.5);
}

.emoji-picker__item--shuffle-in {
    animation: emoji-shuffle-in 0.4s ease backwards;
}

@keyframes emoji-shuffle-in {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.emoji-picker__icon {
    display: block;
    font-size: 2rem;
    margin-bottom: 0.35rem;
}

.emoji-picker__label {
    font-size: 0.85rem;
    font-weight: 600;
}
</style>
