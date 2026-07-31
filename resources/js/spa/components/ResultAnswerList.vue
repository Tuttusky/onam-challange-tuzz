<script setup>
defineProps({
    details: {
        type: Array,
        default: () => [],
    },
    creatorName: {
        type: String,
        default: 'Player',
    },
    challengerName: {
        type: String,
        default: 'Friend',
    },
});

function formatAnswer(answer) {
    if (!answer) return '—';
    if (answer.option_label) return answer.option_label;
    if (answer.text) return answer.text;
    if (answer.media) return '📎 Attachment';
    return '—';
}
</script>

<template>
    <div class="result-answers glass-card">
        <h3 class="result-answers__title">Answer Breakdown</h3>
        <div
            v-for="(item, index) in details"
            :key="item.question_id"
            class="result-answers__item"
            :class="{ 'result-answers__item--match': item.matched }"
        >
            <div class="result-answers__header">
                <span class="result-answers__num">{{ index + 1 }}</span>
                <span class="result-answers__q">{{ item.question_title }}</span>
                <span class="result-answers__icon">{{ item.matched ? '✅' : '❌' }}</span>
            </div>
            <div class="result-answers__row">
                <span class="result-answers__who">{{ creatorName }}</span>
                <span class="result-answers__ans">{{ formatAnswer(item.creator_answer) }}</span>
            </div>
            <div class="result-answers__row">
                <span class="result-answers__who">{{ challengerName }}</span>
                <span class="result-answers__ans">{{ formatAnswer(item.challenger_answer) }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.result-answers {
    padding: 1.25rem;
    margin-top: 1rem;
}

.result-answers__title {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.result-answers__item {
    border-radius: 14px;
    padding: 0.85rem 1rem;
    margin-bottom: 0.75rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.result-answers__item--match {
    border-color: rgba(27, 125, 58, 0.45);
    background: rgba(27, 125, 58, 0.12);
}

.result-answers__header {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.result-answers__num {
    flex-shrink: 0;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.result-answers__q {
    flex: 1;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    line-height: 1.35;
}

.result-answers__icon {
    flex-shrink: 0;
}

.result-answers__row {
    display: flex;
    gap: 0.5rem;
    font-size: 0.82rem;
    margin-top: 0.35rem;
    padding-left: 2rem;
}

.result-answers__who {
    color: rgba(255, 255, 255, 0.55);
    min-width: 4.5rem;
    font-weight: 600;
}

.result-answers__ans {
    color: rgba(255, 255, 255, 0.9);
}
</style>
