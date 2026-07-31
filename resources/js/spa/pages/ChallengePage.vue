<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import GlassLayout from '@/components/GlassLayout.vue';
import { useCampaignStore } from '@/stores/campaign';
import { usePlayerStore } from '@/stores/player';
import { useSessionStore } from '@/stores/session';
import { usePottuStore } from '@/stores/pottu';
import { useUiStore } from '@/stores/ui';
import * as challengesApi from '@/api/challenges';
import * as settingsApi from '@/api/settings';
import { useSound } from '@/composables/useSound';
import { extractChallengeToken } from '@/utils/challengeToken';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';
import { deviceStorage } from '@/utils/deviceStorage';

const route = useRoute();
const router = useRouter();
const campaignStore = useCampaignStore();
const playerStore = usePlayerStore();
const sessionStore = useSessionStore();
const pottuStore = usePottuStore();
const uiStore = useUiStore();
const { tap } = useSound();
const { t } = usePottuFlowI18n();

const token = computed(() => route.params.token);
const name = ref(playerStore.name || deviceStorage.getPlayerName() || '');
const challenge = ref(null);
const campaignSlug = ref('onam-dare-challenge');
const isPottuChallenge = computed(() => challenge.value?.campaign?.type === 'sundarikk_pottu');
const error = ref(null);
const loading = ref(true);

const friendSettings = ref({});
const showHowToModal = ref(false);

const creatorName = computed(() => challenge.value?.creator?.name || t('your_friend'));
const friendName = computed(() => challenge.value?.friend_name || 'you');
const challengeTitle = computed(() => challenge.value?.challenge_title || `${creatorName.value} challenged you!`);
const friendMedia = computed(() => challenge.value?.friend_media ?? null);

const popupEnabled = computed(() => friendSettings.value.show_how_to_play_popup !== false);
const popupTitle = computed(() => friendSettings.value.how_to_play_title || 'How to Play This Challenge 🎯');
const popupSubtitle = computed(() => friendSettings.value.how_to_play_content || "Follow these quick steps to beat your friend's score:");
const popupSteps = computed(() => [
    friendSettings.value.how_to_play_step_1 || 'Enter your name & accept the challenge',
    friendSettings.value.how_to_play_step_2 || 'Drag the pottu dot to the forehead within 30 seconds',
    friendSettings.value.how_to_play_step_3 || 'Check your live accuracy score and beat your friend!',
]);

onMounted(async () => {
    try {
        const [challengeRes, settingsRes] = await Promise.allSettled([
            challengesApi.getByToken(token.value),
            settingsApi.getPublic(),
        ]);

        if (challengeRes.status === 'fulfilled') {
            const payload = challengeRes.value.data.data ?? challengeRes.value.data;
            challenge.value = payload.challenge ?? payload;
            campaignSlug.value = challenge.value?.campaign?.slug || payload.campaign?.slug || 'onam-dare-challenge';
            await campaignStore.fetchBySlug(campaignSlug.value);

            const canonicalToken = extractChallengeToken(payload);
            if (canonicalToken && canonicalToken !== token.value) {
                router.replace({ name: route.name, params: { token: canonicalToken } });
            }
        } else {
            error.value = challengeRes.reason?.response?.data?.message || t('error_not_found');
        }

        if (settingsRes.status === 'fulfilled') {
            const data = settingsRes.value.data.data ?? settingsRes.value.data;
            friendSettings.value = data.friend_challenge ?? {};
        }
    } catch (e) {
        error.value = e.response?.data?.message || t('error_not_found');
    } finally {
        loading.value = false;
    }
});

function handlePlayClick() {
    if (!name.value.trim()) {
        error.value = t('error_name_join');
        return;
    }
    error.value = null;

    if (popupEnabled.value) {
        tap();
        showHowToModal.value = true;
    } else {
        acceptChallenge();
    }
}

async function acceptChallenge() {
    if (!name.value.trim()) {
        error.value = t('error_name_join');
        return;
    }

    showHowToModal.value = false;
    error.value = null;
    uiStore.setLoading(true);
    tap();

    try {
        const trimmedName = name.value.trim();
        playerStore.setName(trimmedName);
        deviceStorage.savePlayerName(trimmedName);
        deviceStorage.savePlayedChallenge(token.value, {
            creatorName: creatorName.value,
            title: challengeTitle.value,
        });
        sessionStore.reset();
        pottuStore.reset();

        const { data } = await challengesApi.join(token.value, {
            name: name.value.trim(),
            uuid: playerStore.uuid,
        });

        sessionStore.hydrateFromStart(data.data ?? data);
        router.push({ name: 'play', params: { slug: campaignSlug.value } });
    } catch (e) {
        error.value = e.response?.data?.message || 'Could not join challenge';
    } finally {
        uiStore.setLoading(false);
    }
}
</script>

<template>
    <GlassLayout>
        <template #header>
            <div class="text-center challenge-hero">
                <div v-if="friendMedia?.type === 'initial'" class="friend-avatar initial">
                    {{ friendMedia.initial }}
                </div>
                <img
                    v-else-if="friendMedia?.url"
                    :src="friendMedia.url"
                    alt="Friend"
                    class="friend-avatar"
                />
                <div v-else class="friend-avatar initial">{{ friendName.charAt(0).toUpperCase() }}</div>

                <h1 class="challenge-title">{{ challengeTitle }}</h1>
                <p v-if="challenge?.challenge_message" class="challenge-message">{{ challenge.challenge_message }}</p>
                <p class="challenge-subtitle" v-if="isPottuChallenge">
                    {{ t('pottu_join_sub', { name: creatorName }) }}
                </p>
                <p v-else class="challenge-subtitle">
                    <strong>{{ creatorName }}</strong>
                    <span v-if="challenge?.creator_score != null"> scored {{ challenge.creator_score }}</span>
                    {{ t('beat_score') }}
                </p>
            </div>
        </template>

        <div v-if="loading" class="text-center text-white py-5">
            <div class="spinner-border text-light" role="status"></div>
        </div>

        <div v-else-if="error && !challenge" class="glass-card p-4 text-center">
            <p class="text-danger mb-3">{{ error }}</p>
            <router-link to="/" class="btn btn-primary-glass">{{ t('go_home') }}</router-link>
        </div>

        <div v-else class="glass-card p-4">
            <label class="form-label text-white-50 small">{{ t('your_name') }}</label>
            <input
                v-model="name"
                type="text"
                class="form-control glass-input form-control-lg mb-3"
                :placeholder="t('enter_name_join')"
                maxlength="50"
                @keyup.enter="handlePlayClick"
            />

            <div v-if="error" class="alert alert-danger-glass mb-3">{{ error }}</div>

            <button type="button" class="btn btn-primary-glass btn-lg w-100 mb-2" @click="handlePlayClick">
                {{ t('play_now') }}
            </button>

            <button type="button" class="btn btn-link text-white-50 btn-sm w-100 text-decoration-none" @click="showHowToModal = true">
                ❓ {{ t('how_to_play') }}
            </button>
        </div>

        <!-- How to Play Popup Modal -->
        <Teleport to="body">
            <div v-if="showHowToModal" class="sheet-backdrop" @click.self="showHowToModal = false">
                <div class="sheet">
                    <div class="sheet__handle"></div>
                    <h2 class="sheet__title">{{ popupTitle }}</h2>
                    <p v-if="popupSubtitle" class="sheet__subtitle">{{ popupSubtitle }}</p>

                    <ul class="howto-list">
                        <li v-for="(stepText, idx) in popupSteps" :key="idx">
                            <span class="howto-list__num">{{ idx + 1 }}</span>
                            <div class="howto-list__body">
                                <p>{{ stepText }}</p>
                            </div>
                        </li>
                    </ul>

                    <button type="button" class="sheet__cta btn-start-challenge w-100" @click="acceptChallenge">
                        <span>{{ t('start_challenge') }}</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </button>
                </div>
            </div>
        </Teleport>
    </GlassLayout>
</template>

<style scoped>
.challenge-hero {
    padding-top: 0.5rem;
}

.friend-avatar {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
    border: 3px solid rgba(129, 140, 248, 0.45);
}

.friend-avatar.initial {
    display: grid;
    place-items: center;
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
}

.challenge-title {
    color: #fff;
    font-weight: 800;
    font-size: 1.65rem;
}

.challenge-message {
    color: rgba(255, 255, 255, 0.88);
    margin: 0.5rem 0;
}

.challenge-subtitle {
    color: rgba(255, 255, 255, 0.82);
}

/* How to Play Sheet Modal */
.sheet-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    animation: fade-in 0.2s ease;
}

.sheet {
    width: min(540px, 100%);
    background: #ffffff;
    border-radius: 28px 28px 0 0;
    padding: 1rem 1.5rem max(1.5rem, env(safe-area-inset-bottom));
    box-shadow: 0 -16px 48px rgba(0, 0, 0, 0.22);
    animation: slide-up 0.25s ease;
}

.sheet__handle {
    width: 44px;
    height: 5px;
    border-radius: 999px;
    background: #e2e8f0;
    margin: 0 auto 1.25rem;
}

.sheet__title {
    margin: 0 0 0.35rem;
    font-size: 1.3rem;
    font-weight: 900;
    color: #1e1b4b;
    text-align: center;
}

.sheet__subtitle {
    margin: 0 0 1.25rem;
    font-size: 0.88rem;
    color: #64748b;
    font-weight: 600;
    text-align: center;
}

.howto-list {
    list-style: none;
    margin: 0 0 1.5rem;
    padding: 0;
    display: grid;
    gap: 0.9rem;
}

.howto-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 0.85rem 1rem;
}

.howto-list__num {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #ffffff;
    font-size: 0.8rem;
    font-weight: 900;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.howto-list__body p {
    margin: 0;
    font-size: 0.88rem;
    color: #1e293b;
    font-weight: 700;
    line-height: 1.35;
}

.btn-start-challenge {
    width: 100%;
    min-height: 52px;
    border: 0;
    border-radius: 999px;
    padding: 0.85rem 1.5rem;
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
    color: #ffffff;
    font-size: 1rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 10px 24px rgba(99, 102, 241, 0.35);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.btn-start-challenge:active {
    transform: scale(0.985);
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slide-up {
    from { transform: translateY(32px); opacity: 0.6; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
