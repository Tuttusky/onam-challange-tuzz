<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import GlassLayout from '@/components/GlassLayout.vue';
import { useCampaignStore } from '@/stores/campaign';
import { usePlayerStore } from '@/stores/player';
import * as challengesApi from '@/api/challenges';
import * as shareCardsApi from '@/api/shareCards';
import { buildChallengeUrl, extractChallengeToken } from '@/utils/challengeToken';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';
import { deviceStorage } from '@/utils/deviceStorage';
import PottuFlowStepper from '@/components/pottu/PottuFlowStepper.vue';

const route = useRoute();
const router = useRouter();
const campaignStore = useCampaignStore();
const playerStore = usePlayerStore();
const { t } = usePottuFlowI18n();

const token = computed(() => route.params.token);
const challengeInfo = ref(null);
const shareCard = ref(null);
const copied = ref(false);

const isPottu = computed(() => {
    return (
        challengeInfo.value?.campaign?.type === 'sundarikk_pottu' ||
        campaignStore.campaign?.type === 'sundarikk_pottu'
    );
});

const shareMessage = computed(
    () => challengeInfo.value?.challenge_message || challengeInfo.value?.share_message || campaignStore.shareMessage
);

const challengeTitle = computed(() => challengeInfo.value?.challenge_title || shareCard.value?.challenge_title || '');

const creatorName = computed(() => challengeInfo.value?.creator?.name || playerStore.name || 'Your friend');
const creatorScore = computed(() => challengeInfo.value?.creator_score ?? 0);
const creatorScoreText = computed(() => {
    if (shareCard.value?.creator_score_label) {
        return shareCard.value.creator_score_label;
    }

    if (isPottu.value) {
        return t('pottu_score_text');
    }

    return `${creatorName.value}'s Score: ${creatorScore.value} – Think you can beat it?`;
});
const gameName = computed(() => challengeInfo.value?.campaign?.name || 'Sundarikk Pottu Thodal');

const activeToken = computed(() => challengeInfo.value?.token ?? token.value);
const shareUrl = computed(() => buildChallengeUrl(activeToken.value));

const shareText = computed(() => {
    const base = shareMessage.value || challengeTitle.value || 'Take my challenge!';
    return `${base}\n${shareUrl.value}`;
});

async function trackShare(channel) {
    try {
        await challengesApi.recordShare(activeToken.value, { channel });
    } catch {
        // Non-blocking
    }
}

function shareWhatsApp() {
    trackShare('whatsapp');
    window.open(`https://wa.me/?text=${encodeURIComponent(shareText.value)}`, '_blank', 'noopener,noreferrer');
}

function shareTelegram() {
    trackShare('telegram');
    window.open(
        `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareMessage.value || challengeTitle.value || 'Challenge!')}`,
        '_blank',
        'noopener,noreferrer'
    );
}

function shareFacebook() {
    trackShare('facebook');
    window.open(
        `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`,
        '_blank',
        'noopener,noreferrer'
    );
}

function shareInstagram() {
    trackShare('instagram');
    copyLink();
}

async function copyLink() {
    trackShare('copy');
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch {
        // Clipboard may be unavailable
    }
}

function goBack() {
    router.push({ name: 'home' });
}

onMounted(async () => {
    if (!campaignStore.campaign) {
        await campaignStore.fetchFeatured();
    }

    try {
        const [{ data: challengeData }, { data: cardData }] = await Promise.all([
            challengesApi.getByToken(token.value),
            shareCardsApi.getCard(token.value),
        ]);
        const payload = challengeData.data ?? challengeData;
        challengeInfo.value = payload.challenge ?? payload;
        shareCard.value = cardData.data ?? cardData;

        const canonicalToken = extractChallengeToken(payload) ?? shareCard.value?.challenge_url?.split('/').pop();
        if (canonicalToken && canonicalToken !== token.value) {
            router.replace({ name: 'share', params: { token: canonicalToken } });
        }

        deviceStorage.saveCreatedChallenge(canonicalToken || token.value, shareUrl.value, {
            creatorName: creatorName.value,
            title: challengeTitle.value,
        });
    } catch {
        // Optional enrichment
    }
});
</script>

<template>
    <GlassLayout :cream="isPottu" :show-footer="false" compact>
        <div class="share-page">
        <PottuFlowStepper v-if="isPottu" :current-step="5" class="mb-3" />

        <!-- Back Button -->
        <button type="button" class="btn-back-circle" @click="goBack">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12" />
                <polyline points="12 19 5 12 12 5" />
            </svg>
        </button>

        <!-- Trophy Header -->
        <div class="trophy-header-container text-center mb-4">
            <div class="trophy-badge-glow">
                <div class="trophy-badge-inner d-flex align-items-center justify-content-center">
                    <img src="/images/logo.png" alt="Sundarikk Pottuthodal Logo" class="trophy-logo-img" />
                </div>
            </div>
            <h1 class="share-page-title mt-3">{{ t('challenge_ready') }}</h1>
            <p class="share-page-subtitle px-3">
                {{ t('share_subtitle') }}<br />{{ t('share_subtitle_line2') }}
            </p>
        </div>

        <!-- Challenge Main Card -->
        <div class="challenge-summary-card mb-4 d-flex align-items-center gap-3">
            <div class="flame-circle-glow flex-shrink-0 d-flex align-items-center justify-content-center">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z" fill="#6366f1" />
                </svg>
            </div>
            <div class="challenge-summary-card__content text-start">
                <h3 class="challenge-summary-card__title">{{ t('challenged_you', { name: creatorName }) }}</h3>
                <p class="challenge-summary-card__game-name mb-1">{{ t('game_label') }}: {{ gameName }}</p>
                <p class="challenge-summary-card__score mb-0">
                    <template v-if="isPottu">{{ creatorScoreText }}</template>
                    <template v-else>
                        {{ creatorName }}'s Score: <strong class="score-red">{{ creatorScore }}</strong> – Think you can beat it?
                    </template>
                </p>
            </div>
        </div>

        <!-- WhatsApp Button (desktop) -->
        <button
            type="button"
            class="btn-whatsapp-large share-page__whatsapp-inline w-100 d-none d-md-flex align-items-center justify-content-between mb-3"
            @click="shareWhatsApp"
        >
            <div class="d-flex align-items-center gap-2">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.455 5.703 1.457h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                </svg>
                <span>{{ t('share_whatsapp') }}</span>
            </div>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6" />
            </svg>
        </button>



        <!-- Dashed Link Copy Bar -->
        <div class="copy-link-dashed-bar d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden me-2">
                <svg class="flex-shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                </svg>
                <span class="copy-link-text">{{ shareUrl }}</span>
            </div>
            <button type="button" class="btn-copy-orange" @click="copyLink">
                {{ copied ? t('copied') : t('copy') }}
            </button>
        </div>

        <!-- View Leaderboard Card -->
        <router-link :to="{ name: 'leaderboard', query: { campaign: challengeInfo?.campaign?.slug || undefined } }" class="view-leaderboard-card d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="view-leaderboard-card__icon-wrapper d-flex align-items-center justify-content-center">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 4v16" />
                        <path d="M18 8v12" />
                        <path d="M6 12v8" />
                        <path d="M3 20h18" />
                    </svg>
                </div>
                <span class="view-leaderboard-card__label">{{ t('view_leaderboard') }}</span>
            </div>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="view-leaderboard-card__chevron">
                <polyline points="9 18 15 12 9 6" />
            </svg>
        </router-link>

        <!-- Challenge Another Friend Card -->
        <router-link :to="{ name: 'home' }" class="challenge-another-friend-card d-flex align-items-stretch">
            <div class="challenge-another-friend-card__content d-flex align-items-center gap-3 flex-grow-1">
                <!-- Kerala Girl Illustration -->
                <div class="challenge-another-friend-card__illustration flex-shrink-0">
                    <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="60" cy="35" r="28" fill="#1e1b4b"/>
                        <circle cx="60" cy="35" r="25" fill="#111827"/>
                        <path d="M32,35 C32,20 88,20 88,35" stroke="#fef08a" stroke-width="8" stroke-linecap="round"/>
                        <path d="M32,35 C32,20 88,20 88,35" stroke="#ffffff" stroke-width="6" stroke-linecap="round" stroke-dasharray="4,6"/>
                        <rect x="52" y="75" width="16" height="20" fill="#fde047"/>
                        <path d="M35,50 C35,32 85,32 85,50 C85,68 78,82 60,82 C42,82 35,68 35,50 Z" fill="#fef08a"/>
                        <path d="M35,46 C48,32 72,32 85,46 C80,36 70,36 60,42 C50,36 40,36 35,46 Z" fill="#111827"/>
                        <circle cx="32" cy="62" r="5" fill="#eab308"/>
                        <path d="M30,65 L34,65 L32,70 Z" fill="#ca8a04"/>
                        <circle cx="88" cy="62" r="5" fill="#eab308"/>
                        <path d="M86,65 L90,65 L88,70 Z" fill="#ca8a04"/>
                        <path d="M42,50 C46,47 51,48 53,51" stroke="#111827" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M78,50 C74,47 69,48 67,51" stroke="#111827" stroke-width="1.8" stroke-linecap="round"/>
                        <circle cx="60" cy="46" r="3.5" fill="#dc2626"/>
                        <path d="M43,56 C46,59 50,59 52,56" stroke="#111827" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                        <path d="M77,56 C74,59 70,59 68,56" stroke="#111827" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                        <path d="M60,54 L58,62 L62,62 Z" fill="#ca8a04" opacity="0.3"/>
                        <path d="M52,69 C55,73 65,73 68,69" fill="#dc2626"/>
                        <path d="M52,69 C55,71 65,71 68,69" stroke="#b91c1c" stroke-width="0.8" fill="none"/>
                        <path d="M46,80 C50,86 70,86 74,80" stroke="#eab308" stroke-width="3" stroke-linecap="round" fill="none"/>
                        <path d="M46,80 C50,86 70,86 74,80" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-dasharray="2,4" fill="none"/>
                    </svg>
                </div>
                <div class="text-start">
                    <span class="challenge-another-friend-card__title">{{ t('challenge_another') }}</span>
                    <span class="challenge-another-friend-card__subtitle">{{ t('challenge_another_sub') }}</span>
                </div>
            </div>
            <div class="challenge-another-friend-card__action d-flex align-items-center justify-content-center">
                <div class="circle-arrow-btn d-flex align-items-center justify-content-center">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </div>
            </div>
        </router-link>
        </div>

        <Teleport to="body">
            <div class="sticky-share-cta d-md-none">
                <button
                    type="button"
                    class="btn-whatsapp-large w-100 d-flex align-items-center justify-content-between"
                    @click="shareWhatsApp"
                >
                    <div class="d-flex align-items-center gap-2">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.455 5.703 1.457h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        <span>{{ t('share_whatsapp') }}</span>
                    </div>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </button>
            </div>
        </Teleport>
    </GlassLayout>
</template>

<style scoped>
.share-page {
    padding-bottom: calc(6rem + env(safe-area-inset-bottom, 0px));
}

@media (min-width: 768px) {
    .share-page {
        padding-bottom: 0;
    }
}

.sticky-share-cta {
    position: fixed;
    left: 50%;
    bottom: 0;
    z-index: 90;
    width: min(640px, 100%);
    transform: translateX(-50%);
    padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
    background: linear-gradient(
        180deg,
        rgba(255, 250, 245, 0) 0%,
        rgba(255, 250, 245, 0.94) 22%,
        #fffaf5 100%
    );
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow: 0 -10px 28px rgba(15, 23, 42, 0.08);
}

/* Redesigned Share Page Styles */
.btn-back-circle {
    position: absolute;
    top: 1.25rem;
    left: 1.25rem;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #ffffff;
    border: 1px solid rgba(229, 231, 235, 0.6);
    color: #1f2937;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    z-index: 10;
    transition: transform 0.15s ease;
}

.btn-back-circle:active {
    transform: scale(0.92);
}

.trophy-header-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 1.5rem;
}

.trophy-badge-glow {
    position: relative;
    width: 90px;
    height: 90px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.trophy-badge-glow::before {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    background: radial-gradient(circle, #fcd34d 0%, rgba(253, 186, 116, 0.45) 60%, transparent 100%);
    z-index: 0;
    opacity: 0.85;
}

.trophy-badge-inner {
    width: 78px;
    height: 78px;
    border-radius: 50%;
    background: #ffffff;
    border: 2.5px solid #fde047;
    padding: 10px;
    z-index: 1;
    box-shadow: 0 8px 20px rgba(217, 119, 6, 0.22);
}

.trophy-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.12));
}

.share-page-title {
    font-size: clamp(1.6rem, 6vw, 2.1rem);
    font-weight: 900;
    color: #ef4444;
    margin: 0;
    line-height: 1.2;
}

.share-page-subtitle {
    font-size: 0.88rem;
    font-weight: 600;
    color: #6b7280;
    margin-top: 0.45rem;
    line-height: 1.45;
}

.challenge-summary-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 1.15rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    border: 1px solid rgba(229, 231, 235, 0.2);
}

.flame-circle-glow {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #e0e7ff;
    box-shadow: 0 0 14px rgba(249, 115, 22, 0.2);
}

.challenge-summary-card__content {
    flex: 1;
}

.challenge-summary-card__title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #1f2937;
    margin: 0 0 0.25rem;
}

.challenge-summary-card__game-name {
    font-size: 0.82rem;
    font-weight: 700;
    color: #6b7280;
}

.challenge-summary-card__score {
    font-size: 0.82rem;
    font-weight: 600;
    color: #6b7280;
}

.score-red {
    color: #ef4444;
    font-weight: 900;
    font-size: 1.05rem;
}

.btn-whatsapp-large {
    min-height: 52px;
    border: 0;
    border-radius: 999px;
    padding: 0.8rem 1.25rem;
    background: #12b76a;
    color: #ffffff;
    font-size: 0.95rem;
    font-weight: 800;
    box-shadow: 0 10px 24px rgba(18, 183, 106, 0.25);
    cursor: pointer;
    transition: transform 0.15s ease;
}

.btn-whatsapp-large:active {
    transform: scale(0.985);
}

.social-share-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.social-share-card {
    border: 0;
    background: #ffffff;
    border-radius: 18px;
    padding: 0.85rem 0.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.45rem;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
    transition: transform 0.15s ease;
}

.social-share-card:active {
    transform: scale(0.94);
}

.social-share-card__icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #4b5563;
}

.social-share-card__icon-wrapper .social-icon--telegram {
    color: #38bdf8;
}

.social-share-card__icon-wrapper .social-icon--facebook {
    color: #1877f2;
}

.social-share-card__icon-wrapper .social-icon--instagram {
    color: #db2777;
}

.social-share-card__label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #4b5563;
}

.copy-link-dashed-bar {
    background: #ffffff;
    border: 1.5px dashed #ddd6fe;
    border-radius: 16px;
    padding: 0.45rem 0.65rem 0.45rem 0.85rem;
}

.copy-link-text {
    font-size: 0.78rem;
    font-weight: 700;
    color: #4b5563;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
}

.btn-copy-orange {
    border: 1.5px solid #6366f1;
    background: #ffffff;
    color: #6366f1;
    font-size: 0.78rem;
    font-weight: 800;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.btn-copy-orange:active {
    transform: scale(0.92);
}

.view-leaderboard-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 0.85rem 1rem;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.03);
    cursor: pointer;
}

.view-leaderboard-card__icon-wrapper {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #fff7ed;
    color: #6366f1;
}

.view-leaderboard-card__label {
    font-size: 0.88rem;
    font-weight: 800;
    color: #1f2937;
}

.view-leaderboard-card__chevron {
    color: #9ca3af;
}

.challenge-another-friend-card {
    background: #fffbeb;
    border: 1.5px solid #fef08a;
    border-radius: 20px;
    padding: 0.65rem 0.85rem;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s ease;
}

.challenge-another-friend-card:active {
    transform: scale(0.985);
}

.challenge-another-friend-card__illustration {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    background: #ffffff;
}

.challenge-another-friend-card__title {
    display: block;
    font-size: 0.85rem;
    font-weight: 800;
    color: #1f2937;
}

.challenge-another-friend-card__subtitle {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    color: #6b7280;
}

.circle-arrow-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(236, 72, 153, 0.3);
}

/* Animations */
.trophy-badge-glow {
    animation: pulse-glow 2.5s infinite;
}

@keyframes pulse-glow {
    0%, 100% { transform: scale(1); opacity: 0.95; }
    50% { transform: scale(1.05); opacity: 1; }
}
</style>
