<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import GlassLayout from '@/components/GlassLayout.vue';
import FriendCompareCard from '@/components/FriendCompareCard.vue';
import ResultAnswerList from '@/components/ResultAnswerList.vue';
import ShareButtons from '@/components/ShareButtons.vue';
import ResultCelebration from '@/components/ResultCelebration.vue';
import ResultComparison from '@/components/pottu/ResultComparison.vue';
import { useCampaignStore } from '@/stores/campaign';
import { usePlayerStore } from '@/stores/player';
import { useSessionStore } from '@/stores/session';
import { usePottuStore } from '@/stores/pottu';
import { useUiStore } from '@/stores/ui';
import { useSound } from '@/composables/useSound';
import * as challengesApi from '@/api/challenges';
import * as pottuApi from '@/api/pottu';
import { extractChallengeToken } from '@/utils/challengeToken';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';

const route = useRoute();
const router = useRouter();
const campaignStore = useCampaignStore();
const playerStore = usePlayerStore();
const sessionStore = useSessionStore();
const pottuStore = usePottuStore();
const uiStore = useUiStore();
const { result: playResultSound } = useSound();
const { t } = usePottuFlowI18n();

const token = computed(() => route.params.token);
const shareToken = computed(() => result.value?.challenge?.token ?? token.value);
const result = ref(null);
const pottuImageUrl = ref('');
const loading = ref(true);
const error = ref(null);
const rematching = ref(false);

const matchPercent = computed(() => Number(result.value?.match_percent ?? 0));
const matchCount = computed(() => result.value?.match_count ?? 0);
const totalQuestions = computed(() => result.value?.total_questions ?? 0);
const badge = computed(() => result.value?.badge ?? null);
const message = computed(() => result.value?.result_message?.message ?? '');
const creatorName = computed(() => result.value?.creator?.name ?? t('player_default'));
const challengerName = computed(() => result.value?.challenger?.name ?? t('friend_default'));
const winnerName = computed(() => result.value?.winner?.name ?? null);
const answerDetails = computed(() => result.value?.answer_details ?? []);
const creatorScore = computed(() => result.value?.creator_score ?? null);
const friendScore = computed(() => result.value?.friend_score ?? matchPercent.value);
const scoreDiff = computed(() => result.value?.score_diff ?? null);
const creatorTime = computed(() => result.value?.creator_completion_time_ms ?? null);
const friendTime = computed(() => result.value?.friend_completion_time_ms ?? null);
const isPottu = computed(() => campaignStore.campaign?.type === 'sundarikk_pottu' || result.value?.challenge?.campaign?.type === 'sundarikk_pottu');
const pixelDistance = computed(() => result.value?.pixel_distance ?? result.value?.score_diff ?? 0);
const pottuLabel = computed(() => result.value?.label ?? 'Result');
const pottuWon = computed(() => result.value?.won ?? false);
const accuracyPercent = computed(() =>
    Math.round(result.value?.accuracy_percent ?? result.value?.accuracy ?? 0)
);
const allFriends = computed(() => result.value?.all_friends ?? []);
const topWinner = computed(() => result.value?.top_winner ?? null);
const displayWinnerName = computed(
    () => topWinner.value?.name ?? winnerName.value ?? challengerName.value
);
const creatorPosition = computed(() => result.value?.creator_position ?? result.value?.answer_details?.creator_position ?? null);
const friendPosition = computed(() => result.value?.friend_position ?? result.value?.answer_details?.friend_position ?? null);
const referenceSize = computed(() => result.value?.reference_size ?? result.value?.answer_details?.reference_size ?? { width: 400, height: 600 });
const canCreateNext = computed(() => result.value?.can_create_next_challenge ?? pottuWon.value);

const shareMessage = computed(() => {
    const pct = Math.round(matchPercent.value);
    return `${creatorName.value} vs ${challengerName.value} scored ${pct}% on the Dare Challenge! Can you beat us?`;
});

onMounted(async () => {
    uiStore.setLoading(true);

    try {
        const challengerUuid =
            route.query.challenger?.toString() ||
            (route.params.resultUuid && route.params.resultUuid !== 'latest'
                ? route.params.resultUuid
                : null) ||
            sessionStore.sessionId;

        const { data } = await challengesApi.getResults(token.value, challengerUuid);
        result.value = data.data ?? data;
        pottuStore.setResult(result.value);

        const canonicalToken = extractChallengeToken(result.value);
        if (canonicalToken && canonicalToken !== token.value) {
            router.replace({
                name: 'result',
                params: { token: canonicalToken, resultUuid: route.params.resultUuid },
                query: route.query,
            });
        }

        const slug = campaignStore.slug || result.value?.challenge?.campaign?.slug || 'onam-dare-challenge';
        if (!campaignStore.campaign) {
            await campaignStore.fetchBySlug(slug);
        }

        if (isPottu.value) {
            const imageId =
                result.value?.friend_position?.image_id ??
                result.value?.creator_position?.image_id ??
                result.value?.answer_details?.friend_position?.image_id ??
                result.value?.answer_details?.creator_position?.image_id ??
                null;

            if (result.value?.answer_details?.image_url) {
                pottuImageUrl.value = result.value.answer_details.image_url;
            } else if (imageId) {
                const { data } = await pottuApi.getConfig(slug);
                const payload = data.data ?? data;
                pottuImageUrl.value =
                    payload.images?.find((image) => image.id === imageId)?.url ?? '';
            }
        }

        playResultSound();
    } catch (e) {
        error.value = e.response?.data?.message || 'Result not found';
    } finally {
        loading.value = false;
        uiStore.setLoading(false);
    }
});

async function handleRematch(type) {
    rematching.value = true;
    uiStore.setLoading(true);

    try {
        const { data } = await challengesApi.rematch(token.value, {
            type,
            player_uuid: playerStore.uuid,
            name: playerStore.name || challengerName.value,
            friend_name: type === 'new_friend' ? undefined : creatorName.value,
        });

        sessionStore.hydrateFromStart(data.data ?? data);
        const slug = campaignStore.slug || 'onam-dare-challenge';
        router.push({ name: 'play', params: { slug } });
    } catch (e) {
        uiStore.showToast(e.response?.data?.message || 'Could not start rematch', 'error');
    } finally {
        rematching.value = false;
        uiStore.setLoading(false);
    }
}

function createNextChallenge() {
    const slug = campaignStore.slug || result.value?.challenge?.campaign?.slug;
    const challengeLink = result.value?.challenge;
    router.push({
        name: 'play',
        params: { slug },
        query: {
            name: playerStore.name,
            parent_link_id: challengeLink?.id ?? undefined,
        },
    });
}

function challengeAnotherFriend() {
    router.push({ name: 'home' });
}
</script>

<template>
    <GlassLayout :show-footer="false" dark>
        <ResultCelebration
            v-if="!loading && !error && !isPottu"
            :match-percent="matchPercent"
            :badge="badge"
            :message="message"
            :winner-name="winnerName"
        />

        <div v-if="loading" class="result-loading text-center text-white py-5">
            <div class="spinner-border text-light mb-3" role="status"></div>
            <p>{{ t('result_loading') }}</p>
        </div>

        <div v-else-if="error" class="glass-card p-4 text-center">
            <h2 class="text-white h5 mb-2">{{ t('result_not_ready') }}</h2>
            <p class="text-white-50 mb-3">{{ error }}</p>
            <router-link to="/" class="btn btn-primary-glass">{{ t('go_home') }}</router-link>
        </div>

        <template v-else>
            <div v-if="isPottu" class="result-hero glass-card text-center">
                <p class="result-hero__eyebrow">{{ t('pottu_result_eyebrow') }}</p>
                <div class="result-hero__score">{{ accuracyPercent }}%</div>
                <p class="result-hero__label">{{ t('accuracy_prefix') }} {{ pottuLabel }}</p>
                <p class="result-hero__label">{{ t('px_apart', { n: Math.round(pixelDistance) }) }}</p>
                <p v-if="displayWinnerName" class="result-hero__winner">
                    {{ t('top_winner') }}: <strong>{{ displayWinnerName }}</strong>
                    <span v-if="topWinner?.accuracy != null"> ({{ Math.round(topWinner.accuracy) }}%)</span>
                </p>
                <p v-if="pottuWon" class="result-hero__message">{{ t('nailed_it') }}</p>
                <p v-else class="result-hero__message">{{ t('so_close') }}</p>
            </div>

            <div v-else class="result-hero glass-card text-center">
                <p class="result-hero__eyebrow">Challenge Result</p>
                <div class="result-hero__score">{{ Math.round(matchPercent) }}%</div>
                <p v-if="winnerName" class="result-hero__label">Winner: {{ winnerName }}</p>
                <div class="result-hero__stats">
                    <span class="result-hero__stat">
                        {{ creatorName }}: <strong>{{ creatorScore ?? matchCount }}</strong>
                    </span>
                    <span class="result-hero__stat">
                        {{ challengerName }}: <strong>{{ friendScore }}</strong>
                    </span>
                </div>
                <p v-if="scoreDiff != null" class="result-hero__diff">Difference: {{ scoreDiff }}</p>
                <p v-if="creatorTime != null" class="result-hero__time">
                    Times: {{ Math.round(creatorTime / 1000) }}s vs {{ Math.round((friendTime || 0) / 1000) }}s
                </p>
                <div v-if="badge" class="result-hero__badge">
                    <span>{{ badge.name }}</span>
                </div>
                <p v-if="message" class="result-hero__message">{{ message }}</p>
            </div>

            <ResultComparison
                v-if="isPottu && creatorPosition"
                :creator-position="creatorPosition"
                :friend-position="friendPosition"
                :friends="allFriends"
                :image-url="pottuImageUrl"
                :reference-size="referenceSize"
                :pixel-distance="pixelDistance"
                :label="pottuLabel"
                :creator-name="creatorName"
                :friend-name="challengerName"
                :top-winner="topWinner"
                class="mt-3"
            />

            <FriendCompareCard
                v-if="!isPottu"
                :creator-name="creatorName"
                :challenger-name="challengerName"
                :match-count="matchCount"
                :total-questions="totalQuestions"
                :match-percent="matchPercent"
                :winner-name="winnerName"
            />

            <ResultAnswerList
                v-if="!isPottu && answerDetails.length"
                :details="answerDetails"
                :creator-name="creatorName"
                :challenger-name="challengerName"
            />

            <div class="glass-card p-4 mt-3">
                <h3 class="text-white h6 mb-3">{{ t('share_again') }}</h3>
                <ShareButtons :token="shareToken" :message="shareMessage" />
            </div>

            <div class="result-actions mt-3 pb-4 d-grid gap-2">
                <button
                    v-if="isPottu && canCreateNext"
                    type="button"
                    class="btn btn-primary-glass"
                    @click="createNextChallenge"
                >
                    {{ t('create_challenge_btn') }}
                </button>
                <button v-if="!isPottu" type="button" class="btn btn-primary-glass" :disabled="rematching" @click="handleRematch('challenge_back')">
                    Challenge Back
                </button>
                <button v-if="!isPottu" type="button" class="btn btn-glass" :disabled="rematching" @click="handleRematch('rematch')">
                    Rematch
                </button>
                <button type="button" class="btn btn-glass" @click="challengeAnotherFriend">
                    {{ t('challenge_another_btn') }}
                </button>
                <router-link
                    :to="{ name: 'leaderboard', query: { campaign: campaignStore.slug || result?.challenge?.campaign?.slug || 'sundarikk-pottu-thodal' } }"
                    class="btn btn-glass"
                >
                    {{ t('view_leaderboard') }}
                </router-link>
            </div>
        </template>
    </GlassLayout>
</template>

<style scoped>
.result-loading p {
    color: rgba(255, 255, 255, 0.75);
}

.result-hero {
    padding: 1.75rem 1.25rem 1.5rem;
    background: rgba(15, 23, 42, 0.9);
    border-color: rgba(255, 255, 255, 0.08);
}

.result-hero__eyebrow {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.5rem;
}

.result-hero__score {
    font-size: 4rem;
    font-weight: 900;
    line-height: 1;
    color: #fff;
}

.result-hero__label,
.result-hero__diff,
.result-hero__time {
    color: rgba(255, 255, 255, 0.85);
}

.result-hero__stats {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin: 0.75rem 0;
}

.result-hero__stat {
    color: rgba(255, 255, 255, 0.8);
}

.result-hero__badge {
    display: inline-flex;
    margin: 0.5rem 0;
    padding: 0.35rem 1rem;
    border-radius: 999px;
    background: rgba(129, 140, 248, 0.15);
    color: var(--accent, #818cf8);
    font-weight: 700;
}

.result-hero__winner {
    color: #fde68a;
    font-weight: 600;
    margin-top: 0.35rem;
}

.result-hero__message {
    color: rgba(255, 255, 255, 0.85);
}
</style>
