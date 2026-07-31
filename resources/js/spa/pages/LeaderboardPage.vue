<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import GlassLayout from '@/components/GlassLayout.vue';
import PottuLeaderboardCompare from '@/components/pottu/PottuLeaderboardCompare.vue';
import { useCampaignStore } from '@/stores/campaign';
import * as leaderboardApi from '@/api/leaderboard';

const route = useRoute();
const campaignStore = useCampaignStore();

const activeTab = ref('daily');
const activeMetric = ref(null);
const entries = ref([]);
const comparisons = ref([]);
const topWinner = ref(null);
const availableMetrics = ref([]);
const metric = ref('highest_match');
const campaignType = ref(null);
const campaignName = ref('');
const loading = ref(false);
const error = ref(null);

const campaignSlug = computed(
    () => route.query.campaign?.toString() || campaignStore.slug || 'sundarikk-pottu-thodal'
);

const isPottu = computed(
    () => campaignType.value === 'sundarikk_pottu' || campaignStore.campaign?.type === 'sundarikk_pottu'
);

const periodTabs = [
    { key: 'daily', label: 'Daily' },
    { key: 'weekly', label: 'Weekly' },
    { key: 'overall', label: 'Overall' },
];

const metricOptions = computed(() => {
    const labels = {
        highest_match: 'Best Match %',
        highest_accuracy: 'Highest Accuracy',
        most_won: 'Most Wins',
        most_created: 'Most Created',
        longest_chain: 'Longest Chain',
        most_shared: 'Most Shares',
        most_invites: 'Most Invites',
    };

    const keys = availableMetrics.value.length
        ? availableMetrics.value
        : isPottu.value
          ? ['highest_accuracy', 'most_won', 'most_created', 'longest_chain']
          : ['highest_match', 'most_shared', 'most_invites'];

    return keys.map((key) => ({ key, label: labels[key] || key }));
});

const scoreSuffix = computed(() => {
    if (metric.value === 'highest_match' || metric.value === 'highest_accuracy') {
        return '%';
    }

    return '';
});

const pageTitle = computed(() => campaignName.value || (isPottu.value ? 'Pottu Challenge Leaderboard' : 'Leaderboard'));
const pageSubtitle = computed(() =>
    isPottu.value
        ? 'Top accuracy scores and friend challenge battles'
        : 'Top players this Onam season'
);

const topWinnerLabel = computed(() => {
    if (!topWinner.value) return '';
    if (metric.value === 'highest_accuracy' || metric.value === 'highest_match') {
        return `${Math.round(topWinner.value.score ?? 0)}% accuracy`;
    }
    if (metric.value === 'most_won') {
        return `${topWinner.value.score ?? 0} wins`;
    }
    return `${topWinner.value.score ?? 0} points`;
});

function formatScore(entry) {
    const value = entry.score ?? entry.match_percent ?? 0;
    return `${value}${scoreSuffix.value}`;
}

async function loadLeaderboard() {
    loading.value = true;
    error.value = null;

    try {
        if (!campaignStore.campaign || campaignStore.slug !== campaignSlug.value) {
            await campaignStore.fetchBySlug(campaignSlug.value);
        }

        const { data } = await leaderboardApi.getForCampaign(
            activeTab.value,
            campaignSlug.value,
            activeMetric.value
        );
        const payload = data.data ?? data;

        entries.value = payload.entries ?? [];
        comparisons.value = payload.comparisons ?? [];
        topWinner.value = payload.top_winner ?? payload.entries?.[0] ?? null;
        metric.value = payload.metric ?? (isPottu.value ? 'highest_accuracy' : 'highest_match');
        campaignType.value = payload.campaign_type ?? campaignStore.campaign?.type ?? null;
        campaignName.value = payload.campaign_name ?? campaignStore.campaign?.name ?? '';
        availableMetrics.value = payload.available_metrics ?? [];

        if (!activeMetric.value) {
            activeMetric.value = metric.value;
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'Could not load leaderboard';
        entries.value = [];
        comparisons.value = [];
        topWinner.value = null;
    } finally {
        loading.value = false;
    }
}

function selectPeriod(tabKey) {
    activeTab.value = tabKey;
    loadLeaderboard();
}

function selectMetric(metricKey) {
    activeMetric.value = metricKey;
    loadLeaderboard();
}

onMounted(loadLeaderboard);

watch(campaignSlug, () => {
    activeMetric.value = null;
    loadLeaderboard();
});
</script>

<template>
    <GlassLayout>
        <template #header>
            <div class="text-center">
                <h1 class="leaderboard-title">{{ pageTitle }}</h1>
                <p class="leaderboard-subtitle">{{ pageSubtitle }}</p>
            </div>
        </template>

        <ul class="nav nav-pills nav-fill mb-3 leaderboard-tabs">
            <li v-for="tab in periodTabs" :key="tab.key" class="nav-item">
                <button
                    type="button"
                    class="nav-link"
                    :class="{ active: activeTab === tab.key }"
                    @click="selectPeriod(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </li>
        </ul>

        <div v-if="metricOptions.length > 1" class="metric-tabs mb-4">
            <button
                v-for="option in metricOptions"
                :key="option.key"
                type="button"
                class="metric-tab"
                :class="{ active: activeMetric === option.key }"
                @click="selectMetric(option.key)"
            >
                {{ option.label }}
            </button>
        </div>

        <div v-if="loading" class="text-center text-white py-4">
            <div class="spinner-border spinner-border-sm text-light" role="status"></div>
        </div>

        <div v-else-if="error" class="alert alert-danger-glass">{{ error }}</div>

        <template v-else>
            <section v-if="topWinner" class="top-winner-card glass-card mb-4">
                <p class="top-winner-card__eyebrow">Top Winner</p>
                <div class="top-winner-card__body">
                    <div class="top-winner-card__avatar">
                        {{ (topWinner.player?.name || topWinner.name || 'P').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h2 class="top-winner-card__name">{{ topWinner.player?.name ?? topWinner.name ?? 'Player' }}</h2>
                        <p class="top-winner-card__score mb-0">{{ topWinnerLabel }}</p>
                    </div>
                    <span class="top-winner-card__rank">#1</span>
                </div>
            </section>

            <section v-if="isPottu && comparisons.length" class="mb-4">
                <h2 class="section-title">Friend Challenge Battles</h2>
                <p class="section-subtitle">See who guessed more accurately and who won each challenge.</p>

                <PottuLeaderboardCompare
                    v-for="comparison in comparisons"
                    :key="comparison.uuid"
                    :comparison="comparison"
                    :is-pottu="true"
                />
            </section>

            <section>
                <h2 class="section-title">
                    {{ isPottu ? 'Top Players' : 'Rankings' }}
                </h2>

                <div v-if="!entries.length" class="glass-card p-4 text-center text-white-50">
                    No scores yet. Complete a challenge to appear here.
                </div>

                <ol v-else class="leaderboard-list list-unstyled mb-0">
                    <li
                        v-for="(entry, index) in entries"
                        :key="entry.player?.uuid || entry.id || index"
                        class="leaderboard-item glass-card"
                    >
                        <span class="leaderboard-rank" :class="{ 'leaderboard-rank--top': index < 3 }">
                            #{{ entry.rank ?? index + 1 }}
                        </span>
                        <div class="leaderboard-info">
                            <strong>{{ entry.player?.name ?? entry.name ?? 'Player' }}</strong>
                            <small class="text-white-50 d-block">
                                {{ entry.metric_label || 'Score' }}
                                <template v-if="isPottu && (metric === 'highest_accuracy' || metric === 'highest_match')">
                                    · Accuracy
                                </template>
                            </small>
                        </div>
                        <span class="leaderboard-score">{{ formatScore(entry) }}</span>
                    </li>
                </ol>
            </section>
        </template>

        <router-link to="/" class="btn btn-glass w-100 mt-4">Back to Home</router-link>
    </GlassLayout>
</template>

<style scoped>
.leaderboard-title {
    color: #fff;
    font-weight: 800;
    font-size: 1.75rem;
}

.leaderboard-subtitle {
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.95rem;
}

.section-title {
    color: #fff;
    font-size: 1.05rem;
    font-weight: 800;
    margin-bottom: 0.35rem;
}

.section-subtitle {
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.85rem;
    margin-bottom: 0.85rem;
}

.leaderboard-tabs .nav-link,
.metric-tab {
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.75);
    border-radius: 999px;
    margin: 0 0.15rem;
    font-weight: 600;
    font-size: 0.85rem;
    border: 1px solid transparent;
}

.leaderboard-tabs .nav-link.active,
.metric-tab.active {
    background: var(--primary);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.2);
}

.metric-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.metric-tab {
    padding: 0.45rem 0.9rem;
}

.leaderboard-item {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.85rem 1rem;
    margin-bottom: 0.65rem;
}

.leaderboard-rank {
    font-weight: 800;
    color: rgba(255, 255, 255, 0.6);
    min-width: 2rem;
}

.leaderboard-rank--top {
    color: var(--accent, #818cf8);
}

.leaderboard-info {
    flex: 1;
    color: #fff;
}

.leaderboard-score {
    font-weight: 800;
    color: var(--primary);
    font-size: 1.1rem;
}

.top-winner-card {
    padding: 1.1rem 1.25rem;
    background: linear-gradient(135deg, rgba(129, 140, 248, 0.14), rgba(99, 102, 241, 0.12));
    border: 1px solid rgba(129, 140, 248, 0.35);
}

.top-winner-card__eyebrow {
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.65rem;
}

.top-winner-card__body {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.top-winner-card__avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 1.4rem;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    flex-shrink: 0;
}

.top-winner-card__name {
    color: #fff;
    font-size: 1.2rem;
    font-weight: 800;
    margin: 0;
}

.top-winner-card__score {
    color: #c7d2fe;
    font-weight: 700;
}

.top-winner-card__rank {
    margin-left: auto;
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--accent, #818cf8);
}
</style>
