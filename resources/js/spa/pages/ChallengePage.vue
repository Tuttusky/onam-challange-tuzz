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
import { useSound } from '@/composables/useSound';
import { extractChallengeToken } from '@/utils/challengeToken';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';

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
const name = ref('');
const challenge = ref(null);
const campaignSlug = ref('onam-dare-challenge');
const isPottuChallenge = computed(() => challenge.value?.campaign?.type === 'sundarikk_pottu');
const error = ref(null);
const loading = ref(true);

const creatorName = computed(() => challenge.value?.creator?.name || t('your_friend'));
const friendName = computed(() => challenge.value?.friend_name || 'you');
const challengeTitle = computed(() => challenge.value?.challenge_title || `${creatorName.value} challenged you!`);
const friendMedia = computed(() => challenge.value?.friend_media ?? null);

onMounted(async () => {
    try {
        const { data } = await challengesApi.getByToken(token.value);
        const payload = data.data ?? data;
        challenge.value = payload.challenge ?? payload;
        campaignSlug.value = challenge.value?.campaign?.slug || payload.campaign?.slug || 'onam-dare-challenge';
        await campaignStore.fetchBySlug(campaignSlug.value);

        const canonicalToken = extractChallengeToken(payload);
        if (canonicalToken && canonicalToken !== token.value) {
            router.replace({ name: route.name, params: { token: canonicalToken } });
        }
    } catch (e) {
        error.value = e.response?.data?.message || t('error_not_found');
    } finally {
        loading.value = false;
    }
});

async function acceptChallenge() {
    if (!name.value.trim()) {
        error.value = t('error_name_join');
        return;
    }

    error.value = null;
    uiStore.setLoading(true);
    tap();

    try {
        playerStore.setName(name.value.trim());
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
                @keyup.enter="acceptChallenge"
            />

            <div v-if="error" class="alert alert-danger-glass mb-3">{{ error }}</div>

            <button type="button" class="btn btn-primary-glass btn-lg w-100" @click="acceptChallenge">
                {{ t('play_now') }}
            </button>
        </div>
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
</style>
