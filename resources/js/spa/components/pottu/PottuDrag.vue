<script setup>
import { ref, computed, watch } from 'vue';
import { pointerToNormalized, captureImageSize } from '@/utils/pottu/CoordinateCapture';
import { estimateAccuracy } from '@/utils/pottu/AccuracyCalculator';

const props = defineProps({
    imageUrl: { type: String, required: true },
    imageWidth: { type: Number, default: null },
    imageHeight: { type: Number, default: null },
    initial: { type: Object, default: null },
    disabled: { type: Boolean, default: false },
    tapOnce: { type: Boolean, default: false },
    dragGuess: { type: Boolean, default: false },
    showMarker: { type: Boolean, default: true },
    creatorPosition: { type: Object, default: null },
    friendPosition: { type: Object, default: null },
    targetPosition: { type: Object, default: null },
    gameSettings: { type: Object, default: () => ({}) },
    referenceSize: { type: Object, default: null },
    liveAccuracyEnabled: { type: Boolean, default: false },
    sessionKey: { type: String, default: '' },
});

const emit = defineEmits(['update:placement', 'guess-placed', 'guess-started', 'live-accuracy']);

const boardRef = ref(null);
const stageRef = ref(null);
const imageRef = ref(null);
const dragLayerRef = ref(null);
const dragging = ref(false);
const hasInteracted = ref(Boolean(props.initial));
const hasPlaced = ref(Boolean(props.initial));
const dotSize = 16;
const position = ref(
    props.initial ? { x: props.initial.x, y: props.initial.y } : null
);
const size = ref(props.initial?.size ?? dotSize);
const rotation = ref(props.initial?.rotation ?? 0);
let lastTapAt = 0;

const livePreview = computed(() => {
    if (!props.liveAccuracyEnabled || !props.targetPosition || !position.value) {
        return null;
    }

    const board = captureImageSize(imageRef.value, stageRef.value);
    const refWidth = props.referenceSize?.width ?? board.boardWidth ?? 400;
    const refHeight = props.referenceSize?.height ?? board.boardHeight ?? 600;

    return estimateAccuracy(props.targetPosition, position.value, {
        refWidth,
        refHeight,
        failThreshold: props.gameSettings.fail_threshold_px ?? 30,
        bands: props.gameSettings.tolerance_bands,
    });
});

const showLiveHud = computed(
    () =>
        props.liveAccuracyEnabled &&
        !props.disabled &&
        !hasPlaced.value &&
        hasInteracted.value &&
        livePreview.value
);

const showFriendMarker = computed(() => {
    if (!props.showMarker || !position.value) {
        return false;
    }

    if (props.creatorPosition && props.friendPosition) {
        return true;
    }

    return hasInteracted.value || Boolean(props.initial);
});

function dotStyle(pos, variant = 'friend') {
    const color = variant === 'creator' ? '#22c55e' : '#dc2626';

    return {
        left: `${pos.x * 100}%`,
        top: `${pos.y * 100}%`,
        width: `${dotSize}px`,
        height: `${dotSize}px`,
        transform: 'translate(-50%, -50%)',
        background: color,
        '--dot-color': color,
    };
}

const friendDotStyle = computed(() => {
    const pos = props.friendPosition && props.creatorPosition
        ? props.friendPosition
        : position.value;
    return pos ? dotStyle(pos, 'friend') : null;
});
const creatorDotStyle = computed(() =>
    props.creatorPosition ? dotStyle(props.creatorPosition, 'creator') : null
);

function placementRect() {
    return imageRef.value?.getBoundingClientRect() ?? stageRef.value?.getBoundingClientRect() ?? null;
}

function buildPlacement() {
    const board = captureImageSize(imageRef.value, stageRef.value);
    return {
        x: position.value.x,
        y: position.value.y,
        size: size.value,
        rotation: rotation.value,
        boardWidth: board.boardWidth,
        boardHeight: board.boardHeight,
    };
}

function emitPlacement() {
    if (!position.value) return;
    emit('update:placement', buildPlacement());
}

function eventCoords(event) {
    if (event.changedTouches?.length) {
        const touch = event.changedTouches[0];
        return { clientX: touch.clientX, clientY: touch.clientY };
    }

    return { clientX: event.clientX, clientY: event.clientY };
}

function captureTarget() {
    return dragLayerRef.value ?? boardRef.value;
}

const canInteract = computed(
    () => !props.tapOnce && !props.disabled && !hasPlaced.value
);

function emitLiveAccuracy() {
    if (!props.liveAccuracyEnabled || hasPlaced.value) {
        emit('live-accuracy', null);
        return;
    }

    emit('live-accuracy', livePreview.value);
}

function placeAt(event) {
    const rect = placementRect();
    if (!rect || props.disabled) return null;
    const { clientX, clientY } = eventCoords(event);
    position.value = pointerToNormalized(clientX, clientY, rect);
    hasInteracted.value = true;
    emitLiveAccuracy();
    return buildPlacement();
}

function commitGuess() {
    if (props.disabled || hasPlaced.value || !position.value) return;
    hasPlaced.value = true;
    emit('live-accuracy', null);
    emit('update:placement', buildPlacement());
    emit('guess-placed', buildPlacement());
}

function commitTap(event) {
    if (!props.tapOnce || props.disabled || hasPlaced.value) return;
    const now = Date.now();
    if (now - lastTapAt < 300) return;
    lastTapAt = now;
    const placement = placeAt(event);
    if (!placement) return;
    commitGuess();
}

function onTapLayerPointerDown(event) {
    if (event.pointerType === 'mouse' && event.button !== 0) return;
    event.preventDefault();
    commitTap(event);
}

function onTapLayerClick(event) {
    event.preventDefault();
    commitTap(event);
}

function resetLocalState(value = props.initial) {
    dragging.value = false;
    hasPlaced.value = Boolean(value);
    hasInteracted.value = Boolean(value);
    position.value = value ? { x: value.x, y: value.y } : null;
    size.value = value?.size ?? dotSize;
    rotation.value = value?.rotation ?? 0;
    emit('live-accuracy', null);
}

watch(
    () => props.initial,
    (value) => {
        resetLocalState(value);
    }
);

watch(
    () => props.sessionKey,
    () => {
        resetLocalState(props.initial);
    }
);

function onPointerDown(event) {
    if (props.tapOnce || props.disabled || hasPlaced.value) return;
    if (event.pointerType === 'mouse' && event.button !== 0) return;

    event.preventDefault();
    dragging.value = true;
    captureTarget()?.setPointerCapture?.(event.pointerId);
    placeAt(event);
    emitPlacement();

    if (props.dragGuess) {
        emit('guess-started');
    }
}

function onPointerMove(event) {
    if (props.tapOnce || !dragging.value || props.disabled || hasPlaced.value) return;
    event.preventDefault();
    placeAt(event);
    emitPlacement();
}

function onPointerUp(event) {
    if (props.tapOnce || !dragging.value) return;

    dragging.value = false;
    captureTarget()?.releasePointerCapture?.(event.pointerId);

    if (props.dragGuess && !hasPlaced.value) {
        placeAt(event);
        commitGuess();
    }
}
</script>

<template>
    <div
        ref="boardRef"
        class="pottu-board"
        :class="{
            'pottu-board--tap': tapOnce && !disabled && !hasPlaced,
            'pottu-board--drag-guess': canInteract,
            'pottu-board--locked': (tapOnce || dragGuess) && hasPlaced,
        }"
    >
        <div ref="stageRef" class="pottu-board__stage">
            <img
                ref="imageRef"
                :src="imageUrl"
                alt="Challenge"
                class="pottu-board__image"
                draggable="false"
                @load="emitPlacement"
            />

            <slot />

            <div
                v-if="canInteract"
                ref="dragLayerRef"
                class="pottu-drag-layer"
                aria-label="Drag to place your guess"
                @pointerdown.stop="onPointerDown"
                @pointermove.stop="onPointerMove"
                @pointerup.stop="onPointerUp"
                @pointercancel.stop="onPointerUp"
            />

            <div
                v-if="tapOnce && !disabled && !hasPlaced"
                class="pottu-tap-layer"
                aria-label="Tap to place your guess"
                @pointerdown="onTapLayerPointerDown"
                @click="onTapLayerClick"
            />

            <div
                v-if="creatorPosition"
                class="pottu-marker pottu-marker--creator"
                :style="creatorDotStyle"
                aria-label="Creator pottu"
            />
            <div
                v-if="showFriendMarker"
                class="pottu-marker pottu-marker--friend"
                :style="friendDotStyle"
                aria-label="Your pottu"
            />

            <div
                v-if="showLiveHud"
                class="pottu-live-hud"
                :class="{ 'pottu-live-hud--win': livePreview?.won }"
            >
                <span class="pottu-live-hud__accuracy">{{ livePreview.accuracyPercent }}%</span>
                <span class="pottu-live-hud__label">{{ livePreview.label }}</span>
                <span class="pottu-live-hud__distance">{{ Math.round(livePreview.pixelDistance) }} px away</span>
                <span v-if="livePreview.won" class="pottu-live-hud__win">Win zone!</span>
                <span v-else class="pottu-live-hud__hint">
                    {{ livePreview.pointsToWin }}px more to win
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pottu-board {
    position: relative;
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    border-radius: 20px;
    overflow: hidden;
    touch-action: none;
    user-select: none;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
}

.pottu-board--tap,
.pottu-board--drag-guess {
    touch-action: none;
}

.pottu-board--drag-guess {
    cursor: crosshair;
}

.pottu-tap-layer {
    position: absolute;
    inset: 0;
    z-index: 5;
    cursor: crosshair;
    touch-action: none;
    -webkit-tap-highlight-color: transparent;
}

.pottu-drag-layer {
    position: absolute;
    inset: 0;
    z-index: 8;
    cursor: crosshair;
    touch-action: none;
    -webkit-tap-highlight-color: transparent;
}

.pottu-board--tap {
    cursor: crosshair;
}

.pottu-board--locked {
    cursor: default;
}

.pottu-board__stage {
    position: relative;
    width: 100%;
    min-height: 120px;
    line-height: 0;
}

.pottu-board__image {
    display: block;
    width: 100%;
    height: auto;
    pointer-events: none;
}

.pottu-live-hud {
    position: absolute;
    top: 12px;
    left: 50%;
    z-index: 16;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    min-width: 140px;
    padding: 0.55rem 1rem;
    border-radius: 14px;
    background: rgba(0, 0, 0, 0.72);
    border: 1px solid rgba(255, 255, 255, 0.15);
    pointer-events: none;
}

.pottu-live-hud--win {
    background: rgba(22, 101, 52, 0.88);
    border-color: rgba(74, 222, 128, 0.45);
}

.pottu-live-hud__accuracy {
    color: #fff;
    font-size: 1.65rem;
    font-weight: 900;
    line-height: 1;
}

.pottu-live-hud__label {
    color: #fde68a;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.pottu-live-hud__distance,
.pottu-live-hud__hint,
.pottu-live-hud__win {
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.72rem;
    font-weight: 600;
}

.pottu-live-hud__win {
    color: #bbf7d0;
    font-weight: 800;
}

.pottu-marker {
    position: absolute;
    z-index: 15;
    pointer-events: none;
    border-radius: 50%;
    background: var(--dot-color, #dc2626);
    border: 2px solid rgba(255, 255, 255, 0.95);
    box-shadow:
        0 0 0 1px rgba(0, 0, 0, 0.2),
        0 2px 8px rgba(220, 38, 38, 0.4);
}

.pottu-marker--creator {
    z-index: 4;
    box-shadow:
        0 0 0 2px rgba(34, 197, 94, 0.35),
        0 2px 8px rgba(34, 197, 94, 0.45);
}
</style>
