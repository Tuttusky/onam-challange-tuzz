<script setup>
import { computed } from 'vue';

const props = defineProps({
    creatorName: { type: String, default: 'Player' },
    challengerName: { type: String, default: 'Friend' },
    matchCount: { type: Number, default: 0 },
    totalQuestions: { type: Number, default: 0 },
    matchPercent: { type: Number, default: 0 },
    winnerName: { type: String, default: null },
});

const mismatchCount = computed(() => props.totalQuestions - props.matchCount);

const compareLabel = computed(() => {
    const pct = Math.round(props.matchPercent);
    if (pct >= 80) return 'Besties! You think alike 🌟';
    if (pct >= 50) return 'Good friends know each other well 🤝';
    if (pct >= 30) return 'Hmm… do you really know each other? 😅';
    return 'Total strangers! Time to hang out more 😂';
});
</script>

<template>
    <div class="friend-compare glass-card">
        <h3 class="friend-compare__title">Compare with Friend</h3>
        <p class="friend-compare__subtitle">{{ compareLabel }}</p>

        <div class="friend-compare__players">
            <div class="friend-compare__player">
                <div class="friend-compare__avatar friend-compare__avatar--creator">
                    {{ creatorName.charAt(0).toUpperCase() }}
                </div>
                <span class="friend-compare__name">{{ creatorName }}</span>
                <span class="friend-compare__tag">Creator</span>
            </div>

            <div class="friend-compare__center">
                <div class="friend-compare__vs">VS</div>
                <div v-if="winnerName" class="friend-compare__winner">
                    🏆 {{ winnerName }}
                </div>
            </div>

            <div class="friend-compare__player">
                <div class="friend-compare__avatar friend-compare__avatar--friend">
                    {{ challengerName.charAt(0).toUpperCase() }}
                </div>
                <span class="friend-compare__name">{{ challengerName }}</span>
                <span class="friend-compare__tag">Friend</span>
            </div>
        </div>

        <div class="friend-compare__bar-wrap">
            <div class="friend-compare__bar">
                <div
                    class="friend-compare__bar-fill"
                    :style="{ width: `${matchPercent}%` }"
                ></div>
            </div>
            <div class="friend-compare__bar-labels">
                <span>✅ {{ matchCount }} matched</span>
                <span>❌ {{ mismatchCount }} different</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.friend-compare {
    padding: 1.25rem;
    margin-top: 1rem;
}

.friend-compare__title {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 800;
    text-align: center;
    margin-bottom: 0.25rem;
}

.friend-compare__subtitle {
    color: rgba(255, 255, 255, 0.75);
    text-align: center;
    font-size: 0.9rem;
    margin-bottom: 1.25rem;
}

.friend-compare__players {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.friend-compare__player {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.friend-compare__avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.35rem;
    color: #fff;
    margin-bottom: 0.5rem;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
}

.friend-compare__avatar--creator {
    background: linear-gradient(135deg, var(--primary), var(--accent, #818cf8));
}

.friend-compare__avatar--friend {
    background: linear-gradient(135deg, var(--secondary), #2d9f5f);
}

.friend-compare__name {
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
}

.friend-compare__tag {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-top: 0.15rem;
}

.friend-compare__center {
    flex-shrink: 0;
    text-align: center;
}

.friend-compare__vs {
    color: var(--accent, #818cf8);
    font-weight: 900;
    font-size: 1.15rem;
}

.friend-compare__winner {
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    margin-top: 0.35rem;
    white-space: nowrap;
}

.friend-compare__bar-wrap {
    margin-top: 0.5rem;
}

.friend-compare__bar {
    height: 12px;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 999px;
    overflow: hidden;
}

.friend-compare__bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--secondary), var(--accent, #818cf8));
    border-radius: 999px;
    transition: width 1s ease;
}

.friend-compare__bar-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 600;
}
</style>
