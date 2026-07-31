<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import GlassLayout from '@/components/GlassLayout.vue';
import { useCampaignStore } from '@/stores/campaign';
import { usePlayerStore } from '@/stores/player';
import { useSessionStore } from '@/stores/session';
import { usePottuStore } from '@/stores/pottu';
import { useUiStore } from '@/stores/ui';
import { useSound } from '@/composables/useSound';

const route = useRoute();
const router = useRouter();
const campaignStore = useCampaignStore();
const playerStore = usePlayerStore();
const sessionStore = useSessionStore();
const pottuStore = usePottuStore();
const uiStore = useUiStore();
const { tap } = useSound();

const slug = computed(() => route.params.slug);
const playerName = ref(route.query.name?.toString() || playerStore.name || '');
const friendName = ref('');
const challengeTitle = ref('');
const challengeMessage = ref('');
const error = ref(null);
const loading = ref(true);

const defaultTitle = computed(() => {
    const name = friendName.value.trim() || 'Friend';
    return isPottu.value
        ? `${playerName.value.trim() || 'I'} challenged you!`
        : `Hey ${name}, Can You Beat Me?`;
});

const isPottu = computed(() => campaignStore.campaign?.type === 'sundarikk_pottu');

onMounted(async () => {
    uiStore.setLoading(true);
    try {
        await campaignStore.fetchBySlug(slug.value);
    } catch (e) {
        error.value = e.response?.data?.message || 'Could not load personalization';
    } finally {
        loading.value = false;
        uiStore.setLoading(false);
    }
});

async function startChallenge() {
    if (!playerName.value.trim()) {
        error.value = 'Your name is required';
        return;
    }

    if (!friendName.value.trim()) {
        error.value = 'Enter your friend\'s name';
        return;
    }

    error.value = null;
    uiStore.setLoading(true);
    tap();

    try {
        playerStore.setName(playerName.value.trim());
        sessionStore.reset();
        pottuStore.reset();

        await sessionStore.start(slug.value, {
            name: playerName.value.trim(),
            uuid: playerStore.uuid,
            friend_name: friendName.value.trim(),
            challenge_title: challengeTitle.value.trim() || defaultTitle.value,
            challenge_message: isPottu.value
                ? (challengeMessage.value.trim() || `${playerName.value.trim()} has challenged you! Can you find the exact pottu position?`)
                : (challengeMessage.value.trim() || null),
            parent_link_id: route.query.parent_link_id ? Number(route.query.parent_link_id) : null,
        });

        router.push({ name: 'play', params: { slug: slug.value } });
    } catch (e) {
        error.value = e.response?.data?.message || 'Could not start challenge';
    } finally {
        uiStore.setLoading(false);
    }
}
</script>

<template>
    <GlassLayout>
        <template #header>
            <div class="text-center">
                <h1 class="page-title">{{ isPottu ? 'Start Pottu Challenge' : 'Personalize Your Challenge' }}</h1>
                <p class="page-subtitle">
                    <template v-if="isPottu">
                        Invite <strong>{{ friendName || 'your friend' }}</strong> to find your secret pottu spot
                    </template>
                    <template v-else>
                        Challenge <strong>{{ friendName || 'your friend' }}</strong> to beat your score
                    </template>
                </p>
            </div>
        </template>

        <div v-if="loading" class="text-center text-white py-5">
            <div class="spinner-border text-light" role="status"></div>
        </div>

        <div v-else class="glass-card p-4">
            <label class="form-label text-white-50 small">Friend's name</label>
            <input
                v-model="friendName"
                type="text"
                class="form-control glass-input form-control-lg mb-3"
                placeholder="Rahul"
                maxlength="50"
            />

            <template v-if="isPottu">
                <label class="form-label text-white-50 small">Challenge title</label>
                <input
                    v-model="challengeTitle"
                    type="text"
                    class="form-control glass-input mb-3"
                    :placeholder="defaultTitle"
                    maxlength="255"
                />
            </template>

            <label class="form-label text-white-50 small">Optional message</label>
            <textarea
                v-model="challengeMessage"
                class="form-control glass-input mb-3"
                rows="2"
                :placeholder="isPottu ? 'Think you can find my pottu?' : 'Think you can beat my score?'"
                maxlength="1000"
            ></textarea>

            <div v-if="error" class="alert alert-danger-glass mb-3">{{ error }}</div>

            <button type="button" class="btn btn-primary-glass btn-lg w-100" @click="startChallenge">
                Start Challenge
            </button>
        </div>
    </GlassLayout>
</template>

<style scoped>
.page-title {
    color: #fff;
    font-weight: 800;
    font-size: 1.8rem;
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.8);
}
</style>
