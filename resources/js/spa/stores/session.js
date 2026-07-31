import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import * as sessionsApi from '@/api/sessions';
import { setSessionToken, getSessionToken } from '@/api/client';

const SESSION_UUID_KEY = 'player_session_uuid';
const SESSION_CAMPAIGN_KEY = 'player_session_campaign';

export const useSessionStore = defineStore('session', () => {
    const sessionId = ref(null);
    const token = ref(null);
    const campaignSlug = ref(null);
    const status = ref('idle');
    const questions = ref([]);
    const currentIndex = ref(0);
    const answers = ref({});
    const challengeToken = ref(null);
    const role = ref('creator');
    const creatorAnswers = ref({});
    const playSettings = ref({
        time_limit_sec: 30,
        shuffle_options: true,
    });

    const totalQuestions = computed(() =>
        questions.value?.mode === 'pottu' ? 1 : questions.value.length
    );
    const currentQuestion = computed(() =>
        questions.value?.mode === 'pottu' ? null : (questions.value[currentIndex.value] ?? null)
    );
    const isPottuMode = computed(() => questions.value?.mode === 'pottu');
    const progress = computed(() =>
        totalQuestions.value ? ((currentIndex.value + 1) / totalQuestions.value) * 100 : 0
    );
    const isComplete = computed(() => currentIndex.value >= totalQuestions.value);
    const allAnswered = computed(() =>
        isPottuMode.value ? true : questions.value.every((q) => answers.value[q.id] !== undefined)
    );

    function shouldLoadQuestions() {
        if (questions.value?.mode === 'pottu') {
            return !(Array.isArray(questions.value.images) && questions.value.images.length > 0);
        }

        return !Array.isArray(questions.value) || questions.value.length === 0;
    }

    function applyQuestionsPayload(payload) {
        if (payload?.mode === 'pottu') {
            questions.value = payload;
            if (payload.session_status) {
                status.value = payload.session_status;
            }
            if (payload.challenge_token) {
                challengeToken.value = payload.challenge_token;
            }
            if (payload.role) {
                role.value = payload.role;
            }
            return;
        }

        questions.value = payload?.questions ?? [];
        creatorAnswers.value = payload?.creator_answers ?? {};

        if (payload?.play_settings) {
            playSettings.value = {
                time_limit_sec: Number(payload.play_settings.time_limit_sec ?? 30),
                shuffle_options: payload.play_settings.shuffle_options !== false,
            };
        }
    }

    function matchesCampaign(slug) {
        if (!campaignSlug.value || !slug) {
            return true;
        }

        return campaignSlug.value === slug;
    }

    function matchesCampaignType(campaignType) {
        const expectsPottu = campaignType === 'sundarikk_pottu';
        return expectsPottu ? isPottuMode.value : !isPottuMode.value;
    }

    function hydrateFromStart(data) {
        const session = data.session ?? data;
        sessionId.value = session?.uuid ?? data.session_id ?? null;
        token.value = data.token ?? null;
        campaignSlug.value =
            session?.campaign?.slug ??
            data.campaign?.slug ??
            data.campaign_slug ??
            campaignSlug.value;
        role.value = session?.role ?? data.role ?? 'creator';
        if (token.value) {
            setSessionToken(token.value);
        }
        if (data.questions?.questions) {
            applyQuestionsPayload(data.questions);
        } else if (data.questions?.mode === 'pottu') {
            applyQuestionsPayload(data.questions);
        } else if (Array.isArray(data.questions)) {
            questions.value = data.questions;
        }
        challengeToken.value =
            data.challenge?.token ??
            data.challenge_link?.token ??
            session?.challenge_link?.token ??
            data.challenge_token ??
            data.questions?.challenge_token ??
            challengeToken.value;
        status.value = 'started';
        currentIndex.value = 0;
        answers.value = {};
        persistSession();
    }

    function persistSession() {
        if (sessionId.value) {
            localStorage.setItem(SESSION_UUID_KEY, sessionId.value);
        }
        if (campaignSlug.value) {
            localStorage.setItem(SESSION_CAMPAIGN_KEY, campaignSlug.value);
        } else {
            localStorage.removeItem(SESSION_CAMPAIGN_KEY);
        }
    }

    function restoreSessionFromStorage() {
        const stored = localStorage.getItem(SESSION_UUID_KEY);
        const storedCampaign = localStorage.getItem(SESSION_CAMPAIGN_KEY);
        if (stored && getSessionToken()) {
            sessionId.value = stored;
            campaignSlug.value = storedCampaign;
            return true;
        }
        return false;
    }

    function clearStaleSession() {
        sessionId.value = null;
        token.value = null;
        campaignSlug.value = null;
        status.value = 'idle';
        questions.value = [];
        currentIndex.value = 0;
        answers.value = {};
        challengeToken.value = null;
        role.value = 'creator';
        creatorAnswers.value = {};
        playSettings.value = {
            time_limit_sec: 30,
            shuffle_options: true,
        };
        setSessionToken(null);
        localStorage.removeItem(SESSION_UUID_KEY);
        localStorage.removeItem(SESSION_CAMPAIGN_KEY);
    }

    async function start(campaignSlug, playerPayload) {
        const { data } = await sessionsApi.start(campaignSlug, playerPayload);
        hydrateFromStart(data.data ?? data);
        return data;
    }

    async function loadQuestions() {
        if (!sessionId.value) return;
        const { data } = await sessionsApi.getQuestions(sessionId.value);
        const payload = data.data ?? data;
        applyQuestionsPayload(payload);
    }

    function getCreatorAnswer(questionId) {
        if (!questionId) {
            return null;
        }

        const raw =
            creatorAnswers.value[questionId] ?? creatorAnswers.value[String(questionId)] ?? null;

        if (!raw) {
            return null;
        }

        return {
            optionId: raw.option_id ?? raw.optionId ?? null,
            text: raw.text ?? raw.answer_text ?? null,
        };
    }

    function setAnswer(questionId, answer) {
        answers.value = {
            ...answers.value,
            [questionId]: answer,
        };
    }

    function nextQuestion() {
        if (currentIndex.value < totalQuestions.value - 1) {
            currentIndex.value += 1;
        } else {
            currentIndex.value = totalQuestions.value;
        }
    }

    function prevQuestion() {
        if (currentIndex.value > 0) {
            currentIndex.value -= 1;
        }
    }

    function goToQuestion(index) {
        if (index >= 0 && index < totalQuestions.value) {
            currentIndex.value = index;
        }
    }

    function buildAnswerPayload() {
        return Object.entries(answers.value).map(([questionId, answer]) => ({
            question_id: Number(questionId),
            option_id: answer.optionId ?? null,
            answer_text: answer.text ?? null,
            answer_media: answer.media ?? null,
        }));
    }

    async function submitAll() {
        if (!sessionId.value) throw new Error('No active session');
        const payload = buildAnswerPayload();
        const { data } = await sessionsApi.submitAnswers(sessionId.value, payload);
        status.value = 'answering';
        return data;
    }

    async function finalize() {
        if (!sessionId.value) throw new Error('No active session');
        const { data } = await sessionsApi.finalize(sessionId.value);
        const payload = data.data ?? data;
        status.value = payload.status ?? 'completed';
        challengeToken.value = payload.challenge_token ?? challengeToken.value;
        return payload;
    }

    function reset() {
        clearStaleSession();
    }

    return {
        sessionId,
        token,
        campaignSlug,
        status,
        questions,
        currentIndex,
        answers,
        challengeToken,
        role,
        creatorAnswers,
        playSettings,
        getCreatorAnswer,
        totalQuestions,
        currentQuestion,
        progress,
        isComplete,
        allAnswered,
        isPottuMode,
        shouldLoadQuestions,
        hydrateFromStart,
        start,
        loadQuestions,
        restoreSessionFromStorage,
        matchesCampaign,
        matchesCampaignType,
        clearStaleSession,
        setAnswer,
        nextQuestion,
        prevQuestion,
        goToQuestion,
        buildAnswerPayload,
        submitAll,
        finalize,
        reset,
    };
});
