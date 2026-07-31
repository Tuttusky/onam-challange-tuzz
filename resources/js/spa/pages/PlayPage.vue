<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import GlassLayout from '@/components/GlassLayout.vue';
import ProgressRing from '@/components/ProgressRing.vue';
import QuestionCard from '@/components/QuestionCard.vue';
import GameEngine from '@/components/pottu/GameEngine.vue';
import { useCampaignStore } from '@/stores/campaign';
import { usePlayerStore } from '@/stores/player';
import { useSessionStore } from '@/stores/session';
import { usePottuStore } from '@/stores/pottu';
import { useUiStore } from '@/stores/ui';
import { useSound } from '@/composables/useSound';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';
import { useLocaleStore } from '@/stores/locale';
import * as challengesApi from '@/api/challenges';

const route = useRoute();
const router = useRouter();
const campaignStore = useCampaignStore();
const playerStore = usePlayerStore();
const sessionStore = useSessionStore();
const pottuStore = usePottuStore();
const uiStore = useUiStore();
const localeStore = useLocaleStore();
const { correct } = useSound();
const { challengeTitle, challengeMessage } = usePottuFlowI18n();

const slug = computed(() => route.params.slug);
const isPottuCampaign = computed(() => campaignStore.campaign?.type === 'sundarikk_pottu');
const isPottu = computed(() => {
    if (sessionStore.sessionId) {
        return sessionStore.isPottuMode;
    }

    return isPottuCampaign.value;
});
const currentAnswer = ref(null);
const submitting = ref(false);
const needsSession = ref(false);
const sessionMismatch = ref(false);
const sessionReady = ref(false);

function invalidatePlaySession() {
    sessionStore.clearStaleSession();
    pottuStore.reset();
    sessionMismatch.value = true;
    needsSession.value = true;
}

async function startFreshSession() {
    const playerName = route.query.name?.toString() || playerStore.name;
    if (!playerName?.trim()) {
        needsSession.value = true;
        return false;
    }

    playerStore.setName(playerName.trim());
    sessionStore.reset();
    pottuStore.reset();

    const title = challengeTitle(playerName.trim());
    const message = challengeMessage(playerName.trim());

    await sessionStore.start(slug.value, {
        name: playerName.trim(),
        uuid: playerStore.uuid,
        friend_name: 'Friend',
        challenge_title: isPottuCampaign.value ? title : 'Hey Friend, Can You Beat Me?',
        challenge_message: isPottuCampaign.value ? message : null,
        parent_link_id: route.query.parent_link_id ? Number(route.query.parent_link_id) : null,
    });

    sessionMismatch.value = false;
    needsSession.value = false;

    if (isPottuCampaign.value && (route.query.name || route.query.lang)) {
        await router.replace({
            name: 'play',
            params: { slug: slug.value },
            query: {},
        });
    }

    return true;
}

onMounted(async () => {
    localeStore.applyFromQuery(route.query.lang?.toString());

    uiStore.setLoading(true);
    try {
        await campaignStore.fetchBySlug(slug.value);

        const playerNameFromQuery = route.query.name?.toString()?.trim() || '';
        const isPottuHomeEntry = isPottuCampaign.value && Boolean(playerNameFromQuery);

        if (isPottuHomeEntry) {
            sessionStore.clearStaleSession();
            const started = await startFreshSession();
            if (!started) {
                return;
            }
        } else if (!sessionStore.sessionId) {
            sessionStore.restoreSessionFromStorage();
        }

        if (!isPottuHomeEntry) {
            if (!sessionStore.sessionId) {
                const started = await startFreshSession();
                if (!started) {
                    return;
                }
            } else if (sessionStore.campaignSlug && !sessionStore.matchesCampaign(slug.value)) {
                invalidatePlaySession();
                return;
            }
        }

        if (sessionStore.shouldLoadQuestions()) {
            await sessionStore.loadQuestions();
        }

        if (
            !isPottuHomeEntry
            && isPottuCampaign.value
            && sessionStore.role === 'creator'
            && sessionStore.questions?.session_status === 'completed'
        ) {
            await startFreshSession();
        }

        if (!sessionStore.matchesCampaignType(campaignStore.campaign?.type)) {
            invalidatePlaySession();
            return;
        }

        const hasContent = sessionStore.isPottuMode
            ? Boolean(sessionStore.questions?.images?.length)
            : sessionStore.questions.length > 0;
        if (!hasContent) {
            const started = await startFreshSession();
            if (!started) {
                needsSession.value = true;
            }
        }
    } catch {
        if (!sessionStore.sessionId) {
            needsSession.value = true;
            return;
        }
        router.replace('/');
    } finally {
        sessionReady.value = Boolean(sessionStore.sessionId) && !needsSession.value;
        uiStore.setLoading(false);
    }
});

async function goToStart() {
    if (isPottuCampaign.value) {
        uiStore.setLoading(true);
        try {
            sessionMismatch.value = false;
            await startFreshSession();
            sessionReady.value = true;
        } catch (e) {
            uiStore.showToast(e.response?.data?.message || 'Could not start challenge', 'error');
        } finally {
            uiStore.setLoading(false);
        }
        return;
    }

    router.replace({ name: 'home' });
}

watch(
    () => sessionStore.currentIndex,
    () => {
        const q = sessionStore.currentQuestion;
        currentAnswer.value = q ? sessionStore.answers[q.id] ?? null : null;
    },
    { immediate: true }
);

async function onAnswerSubmit(answerPayload) {
    const question = sessionStore.currentQuestion;
    const answer = answerPayload ?? currentAnswer.value;
    if (!question || !answer) {
        return;
    }

    sessionStore.setAnswer(question.id, answer);
    currentAnswer.value = answer;

    if (sessionStore.currentIndex < sessionStore.totalQuestions - 1) {
        sessionStore.nextQuestion();
        return;
    }

    await finishQuiz();
}

async function finishQuiz() {
    submitting.value = true;
    uiStore.setLoading(true);

    try {
        await sessionStore.submitAll();
        const result = await sessionStore.finalize();
        correct();

        if (sessionStore.role === 'challenger') {
            const challengeToken = sessionStore.challengeToken;
            await challengesApi.getResults(challengeToken, sessionStore.sessionId);
            router.push({
                name: 'result',
                params: { token: challengeToken },
                query: { challenger: sessionStore.sessionId },
            });
            return;
        }

        router.push({
            name: 'share',
            params: { token: result.challenge_token || sessionStore.challengeToken },
        });
    } catch (e) {
        uiStore.showToast(e.response?.data?.message || 'Failed to submit answers', 'error');
    } finally {
        submitting.value = false;
        uiStore.setLoading(false);
    }
}

function goBack() {
    sessionStore.prevQuestion();
}
</script>

<template>
    <GlassLayout compact :show-footer="false" :cream="isPottu">
        <GameEngine
            v-if="isPottu && sessionStore.sessionId && sessionReady"
            :key="sessionStore.sessionId"
            :campaign-slug="slug"
        />

        <template v-else>
        <div class="play-header d-flex align-items-center justify-content-between mb-3">
            <button
                v-if="sessionStore.currentIndex > 0"
                type="button"
                class="btn btn-glass btn-sm"
                @click="goBack"
            >
                ← Back
            </button>
            <span v-else></span>

            <ProgressRing :progress="sessionStore.progress" :size="64" />

            <span class="play-counter text-white-50 small">
                {{ Math.min(sessionStore.currentIndex + 1, sessionStore.totalQuestions) }}/{{ sessionStore.totalQuestions }}
            </span>
        </div>

        <div class="progress play-progress mb-4">
            <div
                class="progress-bar"
                role="progressbar"
                :style="{ width: `${sessionStore.progress}%` }"
                :aria-valuenow="sessionStore.progress"
                aria-valuemin="0"
                aria-valuemax="100"
            ></div>
        </div>

        <QuestionCard
            v-if="sessionStore.currentQuestion"
            :question="sessionStore.currentQuestion"
            v-model="currentAnswer"
            :creator-answer="sessionStore.getCreatorAnswer(sessionStore.currentQuestion.id)"
            :show-match-feedback="sessionStore.role === 'challenger'"
            :time-limit-sec="sessionStore.playSettings.time_limit_sec"
            :shuffle-options="sessionStore.playSettings.shuffle_options"
            @submit="onAnswerSubmit"
        />

        <div v-else-if="needsSession" class="glass-card p-4 text-center">
            <p class="play-needs-session mb-3">
                {{
                    sessionMismatch
                        ? 'Your saved game session belongs to a different challenge. Start a fresh pottu challenge to continue.'
                        : 'Start the challenge from the home page to play.'
                }}
            </p>
            <button type="button" class="btn btn-primary-glass" @click="goToStart">
                {{ isPottuCampaign ? 'Start Pottu Challenge' : 'Go to Home & Start' }}
            </button>
        </div>

        <div v-else-if="submitting" class="text-center text-white py-5">
            <div class="spinner-border text-light mb-3" role="status"></div>
            <p>Finalizing your challenge...</p>
        </div>
        </template>
    </GlassLayout>
</template>

<style scoped>
.play-header {
    gap: 0.5rem;
}

.play-counter {
    min-width: 48px;
    text-align: right;
    font-weight: 600;
}

.play-progress {
    height: 6px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 999px;
    overflow: hidden;
}

.play-progress .progress-bar {
    background: linear-gradient(90deg, var(--primary), var(--accent, #818cf8));
    transition: width 0.4s ease;
}

.play-needs-session {
    color: #4b5563;
}
</style>
