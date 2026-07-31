<script setup>
import { computed } from 'vue';

const props = defineProps({
    comparison: {
        type: Object,
        required: true,
    },
    isPottu: {
        type: Boolean,
        default: true,
    },
});

const creatorName = computed(() => props.comparison.creator?.name || 'Creator');
const challengerName = computed(() => props.comparison.challenger?.name || 'Friend');
const winnerName = computed(() => props.comparison.winner?.name || null);
const creatorWon = computed(
    () => props.comparison.winner?.uuid && props.comparison.winner?.uuid === props.comparison.creator?.uuid
);
const challengerWon = computed(
    () => props.comparison.winner?.uuid && props.comparison.winner?.uuid === props.comparison.challenger?.uuid
);

const creatorScoreLabel = computed(() => {
    if (props.isPottu) {
        return 'Creator dot';
    }

    const score = props.comparison.creator_score;
    return score != null ? `${Math.round(score)}%` : '—';
});

const challengerScoreLabel = computed(() => {
    if (props.isPottu) {
        const accuracy = props.comparison.challenger_accuracy
            ?? props.comparison.accuracy
            ?? props.comparison.accuracy_percent;
        return accuracy != null ? `${Math.round(accuracy)}% accuracy` : '—';
    }

    const score = props.comparison.challenger_score ?? props.comparison.match_percent;
    return score != null ? `${Math.round(score)}%` : '—';
});

const resultLabel = computed(() => props.comparison.label || null);
const pixelDistance = computed(() => props.comparison.pixel_distance ?? null);
</script>

<template>
    <div class="pottu-compare glass-card">
        <div class="pottu-compare__players">
            <div class="pottu-compare__player" :class="{ 'pottu-compare__player--winner': creatorWon }">
                <div class="pottu-compare__avatar pottu-compare__avatar--creator">
                    {{ creatorName.charAt(0).toUpperCase() }}
                </div>
                <span class="pottu-compare__name">{{ creatorName }}</span>
                <span class="pottu-compare__role">Creator</span>
                <strong class="pottu-compare__score">{{ creatorScoreLabel }}</strong>
                <span v-if="creatorWon" class="pottu-compare__winner-badge">Winner</span>
            </div>

            <div class="pottu-compare__center">
                <span class="pottu-compare__vs">VS</span>
                <span v-if="resultLabel" class="pottu-compare__label">{{ resultLabel }}</span>
                <span v-if="pixelDistance != null" class="pottu-compare__distance">{{ Math.round(pixelDistance) }} px</span>
            </div>

            <div class="pottu-compare__player" :class="{ 'pottu-compare__player--winner': challengerWon }">
                <div class="pottu-compare__avatar pottu-compare__avatar--friend">
                    {{ challengerName.charAt(0).toUpperCase() }}
                </div>
                <span class="pottu-compare__name">{{ challengerName }}</span>
                <span class="pottu-compare__role">Friend</span>
                <strong class="pottu-compare__score">{{ challengerScoreLabel }}</strong>
                <span v-if="challengerWon" class="pottu-compare__winner-badge">Winner</span>
            </div>
        </div>

        <p v-if="isPottu && winnerName" class="pottu-compare__summary text-center mb-0">
            <template v-if="challengerWon">
                <strong>{{ challengerName }}</strong> beat <strong>{{ creatorName }}</strong> with higher accuracy!
            </template>
            <template v-else>
                <strong>{{ creatorName }}</strong> wins — friend was <strong>{{ challengerScoreLabel }}</strong> accurate.
            </template>
        </p>
    </div>
</template>

<style scoped>
.pottu-compare {
    padding: 1rem;
    margin-bottom: 0.75rem;
}

.pottu-compare__players {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 0.75rem;
    align-items: start;
}

.pottu-compare__player {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.2rem;
    padding: 0.65rem;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid transparent;
}

.pottu-compare__player--winner {
    border-color: rgba(129, 140, 248, 0.45);
    background: rgba(129, 140, 248, 0.08);
}

.pottu-compare__avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-weight: 800;
    font-size: 1.2rem;
    color: #fff;
}

.pottu-compare__avatar--creator {
    background: linear-gradient(135deg, #22c55e, #15803d);
}

.pottu-compare__avatar--friend {
    background: linear-gradient(135deg, #dc2626, #991b1b);
}

.pottu-compare__name {
    color: #fff;
    font-weight: 700;
    font-size: 0.92rem;
}

.pottu-compare__role {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.pottu-compare__score {
    color: var(--accent, #818cf8);
    font-size: 1.15rem;
}

.pottu-compare__winner-badge {
    margin-top: 0.15rem;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    background: rgba(129, 140, 248, 0.18);
    color: var(--accent, #818cf8);
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
}

.pottu-compare__center {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding-top: 1.25rem;
}

.pottu-compare__vs {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 900;
}

.pottu-compare__label {
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
}

.pottu-compare__distance {
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.72rem;
}

.pottu-compare__summary {
    margin-top: 0.85rem;
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.88rem;
}
</style>
