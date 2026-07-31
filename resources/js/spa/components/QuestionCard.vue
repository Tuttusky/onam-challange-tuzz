<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import EmojiPicker from './EmojiPicker.vue';
import { useSound } from '@/composables/useSound';

const props = defineProps({
    question: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: Object,
        default: null,
    },
    creatorAnswer: {
        type: Object,
        default: null,
    },
    showMatchFeedback: {
        type: Boolean,
        default: false,
    },
    feedbackDelay: {
        type: Number,
        default: 700,
    },
    timeLimitSec: {
        type: Number,
        default: 0,
    },
    shuffleOptions: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const { tap, correct, wrong } = useSound();
const textValue = ref(props.modelValue?.text ?? '');
const filePreview = ref(props.modelValue?.preview ?? null);
const feedback = ref(null);
const locked = ref(false);
const timeLeft = ref(0);
const shuffleKey = ref(0);
let timerId = null;

const type = computed(() => props.question.type);
const displayOptions = computed(() => props.question.options ?? []);
const hasTimedChoices = computed(() =>
    props.timeLimitSec > 0 && ['yes_no', 'multiple_choice', 'emoji'].includes(type.value)
);
const timerUrgent = computed(() => hasTimedChoices.value && timeLeft.value > 0 && timeLeft.value <= 10);

function stopTimer() {
    if (timerId) {
        window.clearInterval(timerId);
        timerId = null;
    }
}

function onTimeExpired() {
    if (locked.value) {
        return;
    }

    const first = displayOptions.value[0];
    if (!first) {
        return;
    }

    if (type.value === 'emoji') {
        onEmojiSelect(first);
    } else if (type.value === 'yes_no' || type.value === 'multiple_choice') {
        selectOption(first);
    }
}

function startTimer() {
    stopTimer();

    if (!hasTimedChoices.value) {
        timeLeft.value = 0;
        return;
    }

    timeLeft.value = props.timeLimitSec;
    timerId = window.setInterval(() => {
        if (locked.value) {
            return;
        }

        if (timeLeft.value <= 1) {
            timeLeft.value = 0;
            stopTimer();
            onTimeExpired();
            return;
        }

        timeLeft.value -= 1;
    }, 1000);
}

watch(
    () => props.question?.id,
    () => {
        feedback.value = null;
        locked.value = false;
        textValue.value = props.modelValue?.text ?? '';
        filePreview.value = props.modelValue?.preview ?? null;
        shuffleKey.value += 1;
        startTimer();
    },
    { immediate: true }
);

onUnmounted(() => {
    stopTimer();
});

function checkMatch(answer) {
    const creator = props.creatorAnswer;
    if (!creator) {
        return 'correct';
    }

    if (creator.optionId != null && answer.optionId != null) {
        return Number(creator.optionId) === Number(answer.optionId) ? 'correct' : 'wrong';
    }

    if (creator.text && answer.text) {
        return creator.text.trim().toLowerCase() === answer.text.trim().toLowerCase()
            ? 'correct'
            : 'wrong';
    }

    return 'wrong';
}

function applyFeedback(result) {
    feedback.value = result;
    if (result === 'correct') {
        correct();
    } else {
        wrong();
    }
}

function submitWithFeedback(answer) {
    if (locked.value) {
        return;
    }

    stopTimer();
    emit('update:modelValue', answer);

    const canCompare =
        props.showMatchFeedback &&
        props.creatorAnswer &&
        (props.creatorAnswer.optionId != null || props.creatorAnswer.text);

    if (canCompare) {
        locked.value = true;
        applyFeedback(checkMatch(answer));
        setTimeout(() => {
            locked.value = false;
            feedback.value = null;
            emit('submit', answer);
        }, props.feedbackDelay);
        return;
    }

    tap();
    emit('submit', answer);
}

function selectOption(option) {
    submitWithFeedback({
        optionId: option.id,
        value: option.value,
        label: option.label,
    });
}

function submitText() {
    if (!textValue.value.trim()) {
        return;
    }

    submitWithFeedback({ text: textValue.value.trim() });
}

function onEmojiSelect(option) {
    submitWithFeedback({
        optionId: option.id,
        value: option.value,
        label: option.label,
    });
}

function onFileChange(event) {
    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    tap();
    const reader = new FileReader();
    reader.onload = () => {
        filePreview.value = reader.result;
        emit('update:modelValue', {
            media: reader.result,
            preview: reader.result,
            text: file.name,
        });
    };
    reader.readAsDataURL(file);
}

function isSelected(option) {
    return props.modelValue?.optionId === option.id;
}

function isCreatorOption(option) {
    return (
        props.creatorAnswer?.optionId != null &&
        Number(props.creatorAnswer.optionId) === Number(option.id)
    );
}

function optionClass(option) {
    const selected = isSelected(option);

    if (feedback.value && selected) {
        return {
            'question-card__option--correct': feedback.value === 'correct',
            'question-card__option--wrong': feedback.value === 'wrong',
        };
    }

    if (feedback.value === 'wrong' && isCreatorOption(option) && !selected) {
        return { 'question-card__option--correct': true };
    }

    return { 'question-card__option--active': selected };
}

function optionStyle(index) {
    return {
        animationDelay: `${index * 0.07}s`,
    };
}
</script>

<template>
    <article class="question-card glass-card animate-fade-in">
        <div class="question-card__meta">
            <span v-if="shuffleOptions && displayOptions.length > 1" class="question-card__shuffle-badge">
                🔀 Shuffled
            </span>
            <div
                v-if="hasTimedChoices"
                class="question-card__timer"
                :class="{ 'question-card__timer--urgent': timerUrgent }"
            >
                <span class="question-card__timer-value">{{ timeLeft }}</span>
                <span class="question-card__timer-unit">sec</span>
            </div>
        </div>

        <div class="question-card__icon" v-if="question.icon">{{ question.icon }}</div>
        <h2 class="question-card__title">{{ question.title }}</h2>
        <p v-if="question.description" class="question-card__desc">{{ question.description }}</p>

        <div
            v-if="feedback"
            class="question-card__feedback"
            :class="`question-card__feedback--${feedback}`"
        >
            {{ feedback === 'correct' ? '✅ TRUE!' : '❌ WRONG!' }}
        </div>

        <div
            v-if="type === 'yes_no' || type === 'multiple_choice'"
            :key="`options-${shuffleKey}`"
            class="question-card__options"
        >
            <button
                v-for="(option, index) in displayOptions"
                :key="option.id"
                type="button"
                class="btn btn-glass question-card__option question-card__option--shuffle-in"
                :class="optionClass(option)"
                :style="optionStyle(index)"
                :disabled="locked || (hasTimedChoices && timeLeft === 0)"
                @click="selectOption(option)"
            >
                <span v-if="option.icon" class="me-2">{{ option.icon }}</span>
                {{ option.label }}
            </button>
        </div>

        <EmojiPicker
            v-else-if="type === 'emoji'"
            :key="`emoji-${shuffleKey}`"
            :options="displayOptions"
            :model-value="modelValue"
            :feedback="feedback"
            :creator-answer="creatorAnswer"
            :locked="locked || (hasTimedChoices && timeLeft === 0)"
            :shuffle-animated="shuffleOptions"
            @select="onEmojiSelect"
        />

        <div v-else-if="type === 'text'" class="question-card__text">
            <textarea
                v-model="textValue"
                class="form-control glass-input"
                rows="3"
                :placeholder="question.settings?.placeholder || 'Type your answer...'"
                :maxlength="question.settings?.max_length || 500"
                :disabled="locked"
            />
            <button
                type="button"
                class="btn btn-primary-glass w-100 mt-3"
                :disabled="locked"
                @click="submitText"
            >
                Continue
            </button>
        </div>

        <div v-else-if="type === 'image' || type === 'video'" class="question-card__media">
            <label class="btn btn-glass w-100">
                <input
                    type="file"
                    class="d-none"
                    :accept="question.settings?.accept || (type === 'video' ? 'video/*' : 'image/*')"
                    @change="onFileChange"
                />
                {{ type === 'video' ? '🎥 Upload video' : '📸 Upload photo' }}
            </label>
            <img v-if="filePreview && type === 'image'" :src="filePreview" alt="Preview" class="question-card__preview mt-3" />
            <button
                v-if="modelValue"
                type="button"
                class="btn btn-primary-glass w-100 mt-3"
                @click="$emit('submit', modelValue)"
            >
                Continue
            </button>
        </div>
    </article>
</template>

<style scoped>
.question-card {
    padding: 1.5rem;
    text-align: center;
}

.question-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    min-height: 2.25rem;
}

.question-card__shuffle-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.question-card__timer {
    margin-left: auto;
    display: inline-flex;
    align-items: baseline;
    gap: 0.2rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
}

.question-card__timer--urgent {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.55);
    animation: timer-pulse 0.8s ease-in-out infinite;
}

.question-card__timer-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}

.question-card__timer--urgent .question-card__timer-value {
    color: #fca5a5;
}

.question-card__timer-unit {
    font-size: 0.7rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.75);
    text-transform: uppercase;
}

.question-card__icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    animation: pulse 2s ease-in-out infinite;
}

.question-card__title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.5rem;
    line-height: 1.35;
}

.question-card__desc {
    color: rgba(255, 255, 255, 0.78);
    font-size: 0.95rem;
    margin-bottom: 1.25rem;
}

.question-card__feedback {
    margin-bottom: 1rem;
    padding: 0.6rem 1rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: 1rem;
    animation: fade-in 0.25s ease;
}

.question-card__feedback--correct {
    background: rgba(34, 197, 94, 0.25);
    border: 1px solid rgba(34, 197, 94, 0.6);
    color: #86efac;
}

.question-card__feedback--wrong {
    background: rgba(239, 68, 68, 0.25);
    border: 1px solid rgba(239, 68, 68, 0.6);
    color: #fca5a5;
}

.question-card__options {
    display: grid;
    gap: 0.75rem;
}

.question-card__option {
    text-align: left;
    font-weight: 600;
    transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.question-card__option--shuffle-in {
    animation: option-shuffle-in 0.4s ease backwards;
}

.question-card__option--active {
    border-color: var(--accent, #818cf8);
    box-shadow: 0 0 0 2px rgba(129, 140, 248, 0.35);
}

.question-card__option--correct {
    border-color: #22c55e !important;
    background: rgba(34, 197, 94, 0.28) !important;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.5) !important;
    color: #fff !important;
}

.question-card__option--wrong {
    border-color: #ef4444 !important;
    background: rgba(239, 68, 68, 0.28) !important;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.5) !important;
    color: #fff !important;
}

.question-card__preview {
    width: 100%;
    max-height: 220px;
    object-fit: cover;
    border-radius: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes option-shuffle-in {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes timer-pulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.04);
    }
}
</style>
