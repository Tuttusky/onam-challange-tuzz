<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import PottuDrag from './PottuDrag.vue';
import RevealOverlay from './RevealOverlay.vue';
import { useSessionStore } from '@/stores/session';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';
import { resolvePottuImageUrl } from '@/composables/resolvePottuImageUrl';

const props = defineProps({
    image: { type: Object, required: true },
    settings: { type: Object, default: () => ({}) },
    role: { type: String, default: 'creator' },
    placement: { type: Object, default: null },
    showResult: { type: Boolean, default: false },
    creatorPosition: { type: Object, default: null },
    friendPosition: { type: Object, default: null },
    resultLabel: { type: String, default: '' },
    accuracyPercent: { type: Number, default: null },
    pixelDistance: { type: Number, default: null },
    sessionKey: { type: String, default: '' },
});

const emit = defineEmits(['update:placement', 'guess-placed', 'time-expired']);

const sessionStore = useSessionStore();
const { t } = usePottuFlowI18n();
const isChallenger = computed(
    () => props.role === 'challenger' || sessionStore.role === 'challenger'
);
const canDragGuess = computed(() => isChallenger.value && !props.showResult);
const guessStarted = ref(false);
const timeLimit = computed(() => Number(props.settings.time_limit_sec ?? 30));
const timeLeft = ref(timeLimit.value);
const timerDone = ref(false);
let timerId = null;

const overlayVisible = computed(
    () =>
        isChallenger.value &&
        !props.showResult &&
        !guessStarted.value &&
        props.settings.overlay_enabled !== false
);

const imageUrl = computed(() => resolvePottuImageUrl(props.image));
const liveAccuracy = ref(null);

function onLiveAccuracy(payload) {
    liveAccuracy.value = payload;
}

function onGuessPlaced(placement) {
    emit('guess-placed', placement);
}

function onGuessStarted() {
    guessStarted.value = true;
}

function startTimer() {
    if (!isChallenger.value || props.showResult) return;
    clearTimer();
    timeLeft.value = timeLimit.value;
    timerDone.value = false;
    timerId = window.setInterval(() => {
        if (timeLeft.value <= 1) {
            timeLeft.value = 0;
            timerDone.value = true;
            clearTimer();
            emit('time-expired');
            return;
        }
        timeLeft.value -= 1;
    }, 1000);
}

function clearTimer() {
    if (timerId) {
        window.clearInterval(timerId);
        timerId = null;
    }
}

onMounted(startTimer);
onUnmounted(clearTimer);

watch(
    () => props.showResult,
    (revealed) => {
        if (revealed) {
            clearTimer();
            liveAccuracy.value = null;
        }
    }
);

watch(
    () => props.sessionKey,
    () => {
        guessStarted.value = false;
        liveAccuracy.value = null;
    }
);
</script>

<template>
    <div class="pottu-challenge">
        <div class="board-frame">
            <div v-if="isChallenger && !showResult" class="board-frame__timer" :class="{ urgent: timeLeft <= 10 }">
                <span class="board-frame__timer-value">{{ timeLeft }}</span>
                <span class="board-frame__timer-unit">{{ t('sec_unit') }}</span>
            </div>

            <div
                v-if="isChallenger && !showResult && liveAccuracy"
                class="board-frame__accuracy"
                :class="{ 'board-frame__accuracy--win': liveAccuracy.won }"
            >
                <span class="board-frame__accuracy-value">{{ liveAccuracy.accuracyPercent }}%</span>
                <span class="board-frame__accuracy-label">{{ liveAccuracy.label }}</span>
                <span class="board-frame__accuracy-distance">
                    {{ t('px_away', { n: Math.round(liveAccuracy.pixelDistance) }) }}
                </span>
            </div>

            <div class="board-frame__inner">
                <PottuDrag
                    :key="sessionKey || image.id"
                    :image-url="imageUrl"
                    :image-width="image.width"
                    :image-height="image.height"
                    :initial="showResult ? placement : null"
                    :disabled="showResult || timerDone"
                    :tap-once="false"
                    :drag-guess="canDragGuess"
                    :show-marker="true"
                    :creator-position="showResult ? creatorPosition : null"
                    :friend-position="showResult ? friendPosition : placement"
                    :target-position="settings.creator_target"
                    :game-settings="settings"
                    :reference-size="settings.reference_size"
                    :live-accuracy-enabled="isChallenger && !showResult && Boolean(settings.creator_target)"
                    :session-key="sessionKey"
                    @update:placement="emit('update:placement', $event)"
                    @guess-placed="onGuessPlaced"
                    @guess-started="onGuessStarted"
                    @live-accuracy="onLiveAccuracy"
                >
                    <RevealOverlay
                        v-if="overlayVisible"
                        :color="settings.overlay_color || '#FFFFFF'"
                        :opacity="settings.overlay_opacity ?? 1"
                        :hint="t('drag_hint')"
                    />
                </PottuDrag>
            </div>

            <div v-if="isChallenger && timerDone && !showResult" class="board-frame__expired glass-card p-3 mt-2 text-center">
                <p class="mb-0 fw-bold">{{ t('time_up_title') }}</p>
                <p class="small mb-0">{{ t('time_up_sub') }}</p>
            </div>

            <div v-if="isChallenger && showResult" class="guess-result glass-card p-3 mt-3 text-center">
                <p class="guess-result__label small mb-1">{{ resultLabel || t('result_label') }}</p>
                <p v-if="accuracyPercent != null" class="guess-result__accuracy mb-1">
                    {{ t('accuracy_label') }}: <strong>{{ Math.round(accuracyPercent) }}%</strong>
                </p>
                <p v-if="pixelDistance != null" class="guess-result__distance small mb-0">
                    {{ t('pixels_away', { n: Math.round(pixelDistance) }) }}
                </p>
                <p class="guess-result__legend small mt-2 mb-0">
                    <span class="legend-dot legend-dot--creator"></span> {{ t('legend_creator') }}
                    <span class="legend-dot legend-dot--friend ms-3"></span> {{ t('legend_your_guess') }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pottu-challenge {
    width: 100%;
}

.board-frame {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.board-frame__timer {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.35rem;
    min-width: 120px;
    padding: 0.5rem 1.25rem;
    border-radius: 999px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}

.board-frame__timer.urgent {
    background: #fff5f5;
    box-shadow: 0 4px 20px rgba(220, 38, 38, 0.2);
}

.board-frame__timer-value {
    font-size: 2rem;
    font-weight: 900;
    line-height: 1;
    color: #dc2626;
}

.board-frame__timer.urgent .board-frame__timer-value {
    color: #b91c1c;
}

.board-frame__timer-unit {
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: rgba(0, 0, 0, 0.45);
}

.board-frame__inner {
    width: 100%;
    padding: 10px;
    border-radius: 24px;
    background: #ffffff;
    box-shadow:
        0 0 0 1px rgba(255, 255, 255, 0.8),
        0 12px 40px rgba(0, 0, 0, 0.15);
}

.board-frame__inner :deep(.pottu-board) {
    border-radius: 16px;
    box-shadow: none;
}

.board-frame__accuracy {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    width: 100%;
    max-width: 280px;
    padding: 0.65rem 1rem;
    border-radius: 16px;
    background: rgba(0, 0, 0, 0.78);
    border: 1px solid rgba(255, 255, 255, 0.12);
    text-align: center;
}

.board-frame__accuracy--win {
    background: rgba(22, 101, 52, 0.9);
    border-color: rgba(74, 222, 128, 0.45);
}

.board-frame__accuracy-value {
    color: #fff;
    font-size: 2rem;
    font-weight: 900;
    line-height: 1;
}

.board-frame__accuracy-label {
    color: #fde68a;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.board-frame__accuracy-distance {
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.78rem;
    font-weight: 600;
}

.board-frame__expired {
    width: 100%;
    color: #991b1b;
    background: #fff5f5;
    border: 1px solid rgba(220, 38, 38, 0.2);
}

.guess-result__accuracy {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1f2937;
}

.guess-result__label,
.guess-result__distance,
.guess-result__legend {
    color: #6b7280;
}

.legend-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 0.35rem;
    vertical-align: middle;
}

.legend-dot--creator {
    background: #22c55e;
}

.legend-dot--friend {
    background: #dc2626;
}
</style>
