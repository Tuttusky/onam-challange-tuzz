<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import PottuChallenge from './PottuChallenge.vue';
import { usePlayerStore } from '@/stores/player';
import { usePottuStore } from '@/stores/pottu';
import { useSessionStore } from '@/stores/session';
import { useUiStore } from '@/stores/ui';
import { useSound } from '@/composables/useSound';
import * as pottuApi from '@/api/pottu';
import * as challengesApi from '@/api/challenges';
import client from '@/api/client';
import { compressImageFile } from '@/composables/compressImage';
import { resolvePottuImageUrl } from '@/composables/resolvePottuImageUrl';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';
import PottuFlowStepper from './PottuFlowStepper.vue';

const props = defineProps({
    campaignSlug: { type: String, required: true },
});

const router = useRouter();
const playerStore = usePlayerStore();
const pottuStore = usePottuStore();
const sessionStore = useSessionStore();
const uiStore = useUiStore();
const { tap, correct } = useSound();
const { t } = usePottuFlowI18n();

const submitting = ref(false);
const step = ref('image');
const showChallengerResult = ref(false);
const challengerResult = ref(null);

const isCreator = computed(() => pottuStore.role === 'creator');
const isChallenger = computed(() => pottuStore.role === 'challenger');
const challengeToken = computed(() => sessionStore.challengeToken);
const currentStep = computed(() => {
    if (step.value === 'image') return 3;
    if (step.value === 'play' && isCreator.value && !showChallengerResult.value) return 4;
    return 5;
});

const howToSteps = computed(() => [
    { title: t('howto_choose_image'), text: t('howto_choose_image_text') },
    { title: t('howto_set_pottu'), text: t('howto_set_pottu_text') },
    { title: t('howto_invite'), text: t('howto_invite_text') },
]);

const resultLabel = computed(() => challengerResult.value?.label ?? '');
const accuracyPercent = computed(() => challengerResult.value?.accuracy ?? challengerResult.value?.accuracy_percent ?? null);
const pixelDistance = computed(() => challengerResult.value?.pixel_distance ?? challengerResult.value?.score_diff ?? null);
const creatorPosition = computed(() => challengerResult.value?.creator_position ?? null);
const friendPosition = computed(() => challengerResult.value?.friend_position ?? pottuStore.placement);

// Edit Name Widget state
const isEditingName = ref(false);
const tempName = ref(playerStore.name || '');
const nameInput = ref(null);
const showHowTo = ref(false);

const customRetentionDays = computed(() => Number(pottuStore.settings.custom_image_retention_days ?? 7));
const customChallengeDays = computed(() => Number(pottuStore.settings.custom_challenge_valid_days ?? 7));
const customPrivacyNotice = computed(
    () => pottuStore.settings.custom_photo_privacy_notice
        ?? `Custom photos are stored for ${customRetentionDays.value} days only, then automatically deleted from our servers.`
);

function isCustomImage(image) {
    return Boolean(image?.is_custom || image?.title === 'Custom Image');
}

const showImageStepCta = computed(() => step.value === 'image' && isCreator.value);
const showCreatorConfirmCta = computed(
    () => step.value !== 'image'
        && Boolean(pottuStore.selectedImage)
        && isCreator.value
        && !showChallengerResult.value
);
const showChallengerResultCta = computed(
    () => step.value !== 'image'
        && Boolean(pottuStore.selectedImage)
        && isChallenger.value
        && showChallengerResult.value
);
const showStickyCta = computed(
    () => showImageStepCta.value || showCreatorConfirmCta.value || showChallengerResultCta.value
);

async function saveName() {
    if (!tempName.value.trim()) {
        tempName.value = playerStore.name || '';
        isEditingName.value = false;
        return;
    }
    isEditingName.value = false;
    playerStore.setName(tempName.value.trim());
    try {
        await client.post('/players', {
            name: tempName.value.trim(),
            player_uuid: playerStore.uuid
        });
    } catch (err) {
        console.error('Failed to save name on backend', err);
    }
}

function startEditing() {
    isEditingName.value = true;
    tempName.value = playerStore.name || '';
    nextTick(() => {
        nameInput.value?.focus();
    });
}

onMounted(async () => {
    pottuStore.reset();

    if (sessionStore.shouldLoadQuestions() && sessionStore.sessionId) {
        await sessionStore.loadQuestions();
    }

    const payload = sessionStore.questions?.mode ? sessionStore.questions : sessionStore.questions;

    if (payload?.mode === 'pottu') {
        pottuStore.hydrateFromBootstrap({
            ...payload,
            role: payload.role ?? sessionStore.role ?? 'creator',
        });
        if ((payload.role ?? sessionStore.role) === 'challenger') {
            pottuStore.setPlacement(null);
        }
        if (!pottuStore.images.length) {
            await pottuStore.loadConfig(props.campaignSlug);
        }
    } else if (!pottuStore.images.length) {
        await pottuStore.loadConfig(props.campaignSlug);
    }

    // Pre-select first image for convenience, but always show the picker for creators.
    if (isCreator.value && pottuStore.images.length > 0) {
        if (!pottuStore.selectedImageId) {
            pottuStore.selectedImageId = pottuStore.images[0].id;
        }
        step.value = 'image';
    } else {
        step.value = 'play';
    }
});

function selectImage(id) {
    pottuStore.selectedImageId = id;
    tap();
}

function continueToSetPottu() {
    if (!pottuStore.selectedImageId) {
        uiStore.showToast(t('select_image_first'), 'error');
        return;
    }
    step.value = 'play';
    tap();
}

function onPlacement(value) {
    pottuStore.setPlacement({
        ...value,
        imageId: pottuStore.selectedImageId,
        styleId: pottuStore.selectedStyleId,
    });
}

async function submitPlacement() {
    if (!pottuStore.placement || !pottuStore.selectedImageId) {
        uiStore.showToast(t('place_pottu_first'), 'error');
        return false;
    }

    submitting.value = true;
    uiStore.setLoading(true);

    try {
        const placement = {
            imageId: pottuStore.selectedImageId,
            styleId: pottuStore.selectedStyleId,
            x: pottuStore.placement.x,
            y: pottuStore.placement.y,
            size: pottuStore.placement.size,
            rotation: pottuStore.placement.rotation ?? 0,
            boardWidth: pottuStore.placement.boardWidth,
            boardHeight: pottuStore.placement.boardHeight,
        };

        await ensurePlayerSynced();

        await pottuApi.submitPlacement(
            sessionStore.sessionId,
            placement,
            isChallenger.value ? challengeToken.value : null
        );

        await sessionStore.finalize();
        correct();
        return true;
    } catch (e) {
        const message = e.response?.data?.message || '';
        if (message.toLowerCase().includes('already completed') && sessionStore.challengeToken) {
            router.push({
                name: 'share',
                params: { token: sessionStore.challengeToken },
            });
            return false;
        }
        uiStore.showToast(message || 'Could not save placement', 'error');
        return false;
    } finally {
        submitting.value = false;
        uiStore.setLoading(false);
    }
}

async function confirmChallenge() {
    tap();
    const ok = await submitPlacement();
    if (!ok) return;

    if (isChallenger.value) {
        await revealChallengerResult();
        return;
    }

    router.push({
        name: 'share',
        params: { token: sessionStore.challengeToken },
    });
}

async function onChallengerGuess(placement) {
    if (submitting.value || showChallengerResult.value) return;
    if (placement) {
        onPlacement(placement);
    }
    tap();
    const ok = await submitPlacement();
    if (!ok) return;
    await revealChallengerResult();
}

async function onTimeExpired() {
    if (submitting.value || showChallengerResult.value) return;

    if (pottuStore.placement) {
        await onChallengerGuess(pottuStore.placement);
        return;
    }

    uiStore.showToast(t('time_up_missed'), 'error');
}

async function ensurePlayerSynced() {
    const name = playerStore.name?.trim();
    if (!name) return;

    try {
        await client.post('/players', {
            name,
            player_uuid: playerStore.uuid,
        });
    } catch {
        // Non-blocking — finalize should still proceed.
    }
}

async function revealChallengerResult() {
    try {
        const { data } = await challengesApi.getResults(challengeToken.value, sessionStore.sessionId);
        challengerResult.value = data.data ?? data;
        pottuStore.setResult(challengerResult.value);
        showChallengerResult.value = true;
    } catch (e) {
        uiStore.showToast(e.response?.data?.message || 'Could not load result', 'error');
    }
}

function goToFullResult() {
    router.push({
        name: 'result',
        params: { token: challengeToken.value },
        query: { challenger: sessionStore.sessionId },
    });
}

function createNextChallenge() {
    if (!challengerResult.value?.can_create_next_challenge) return;
    router.push({
        name: 'play',
        params: { slug: props.campaignSlug },
        query: {
            name: playerStore.name || undefined,
            parent_link_id: challengerResult.value?.challenge?.id,
        },
    });
}

async function handleFileUpload(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
        uiStore.showToast('Image must be less than 10MB', 'error');
        return;
    }

    pottuStore.images = pottuStore.images.filter(
        (image) => image.title !== 'Custom Image' && !String(image.id).startsWith('temp-')
    );

    const previewUrl = URL.createObjectURL(file);
    const tempId = `temp-${Date.now()}`;
    const tempImage = {
        id: tempId,
        title: 'Custom Image',
        url: previewUrl,
        previewUrl,
        is_custom: true,
        isUploading: true,
    };

    pottuStore.images.push(tempImage);
    selectImage(tempId);

    uiStore.setLoading(true);
    try {
        const compressed = await compressImageFile(file);
        const { data } = await pottuApi.uploadImage(props.campaignSlug, compressed);
        const uploaded = data.data ?? data;
        const newImage = {
            ...uploaded,
            url: resolvePottuImageUrl(uploaded),
            previewUrl,
        };
        const index = pottuStore.images.findIndex((image) => image.id === tempId);

        if (index !== -1) {
            pottuStore.images[index] = newImage;
        } else {
            pottuStore.images.push(newImage);
        }

        selectImage(newImage.id);
        uiStore.showToast('Custom image uploaded', 'success');
    } catch (e) {
        pottuStore.images = pottuStore.images.filter((image) => image.id !== tempId);
        if (pottuStore.selectedImageId === tempId) {
            pottuStore.selectedImageId = pottuStore.images[0]?.id ?? null;
        }
        uiStore.showToast(e.response?.data?.message || e.message || 'Could not upload image', 'error');
    } finally {
        URL.revokeObjectURL(previewUrl);
        uiStore.setLoading(false);
        if (event.target) {
            event.target.value = '';
        }
    }
}

function onImageError(image, event) {
    const img = event.target;
    if (!img || image._retried) {
        return;
    }

    image._retried = true;
    const fallback = resolvePottuImageUrl(image);

    if (fallback && img.src !== fallback) {
        img.src = fallback;
    }
}
</script>

<template>
    <div class="game-engine">
        <!-- Top bar (only for Creator choosing steps) -->
        <header v-if="isCreator" class="topbar-pottu d-flex align-items-center justify-content-between mb-3">
            <div class="brand" aria-label="Sundarikk Pottuthodal">
                <img src="/images/logo.png" alt="Sundarikk Pottuthodal" class="brand__logo-img" />
            </div>
            <button type="button" class="btn-help" @click="showHowTo = true">
                <span class="btn-help__icon">?</span>
                <span>{{ t('how_to_play') }}</span>
            </button>
        </header>

        <!-- Stepper (only for Creator choosing steps) -->
        <PottuFlowStepper v-if="isCreator" :current-step="currentStep" class="mb-4" />

        <!-- Step 1: Choose Image Screen -->
        <template v-if="step === 'image'">
            <div class="choose-title-section text-center mb-3">
                <h1 class="choose-title">{{ t('choose_image_title') }}</h1>
                <p class="choose-subtitle">{{ t('choose_image_sub') }}</p>
            </div>

            <!-- Tags Row -->
            <div class="badges-row d-flex justify-content-center align-items-center gap-2 mb-3">
                <div class="badge-tag badge-tag--hd">
                    <span class="badge-tag__icon badge-tag__icon--hd">HD</span>
                    <span>{{ t('tag_hd') }}</span>
                </div>
                <div class="badge-tag">
                    <svg class="badge-tag__icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M8 14s1.5 2 4 2 4-2 4-2" />
                        <line x1="9" y1="9" x2="9.01" y2="9" stroke-linecap="round" />
                        <line x1="15" y1="9" x2="15.01" y2="9" stroke-linecap="round" />
                    </svg>
                    <span>{{ t('tag_forehead') }}</span>
                </div>
                <div class="badge-tag">
                    <svg class="badge-tag__icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span>{{ t('tag_front_face') }}</span>
                </div>
                <div class="badge-tag">
                    <svg class="badge-tag__icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2" />
                        <path d="M12 20v2" />
                        <path d="M4.93 4.93l1.41 1.41" />
                        <path d="M17.66 17.66l1.41 1.41" />
                        <path d="M2 12h2" />
                        <path d="M20 12h2" />
                        <path d="M6.34 17.66l-1.41 1.41" />
                        <path d="M19.07 4.93l-1.41 1.41" />
                    </svg>
                    <span>{{ t('tag_lighting') }}</span>
                </div>
            </div>

            <!-- Profile Name Card -->
            <div class="profile-card d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3 flex-grow-1">
                    <div class="profile-card__avatar d-flex align-items-center justify-content-center">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                    <div class="profile-card__info flex-grow-1">
                        <span class="profile-card__label">{{ t('profile_name_label') }}</span>
                        <div v-if="isEditingName" class="d-flex align-items-center gap-2 mt-1">
                            <input
                                v-model="tempName"
                                ref="nameInput"
                                type="text"
                                class="form-control name-edit-input"
                                @keyup.enter="saveName"
                                @blur="saveName"
                                :placeholder="t('profile_name_placeholder')"
                                maxlength="20"
                            />
                        </div>
                        <span v-else class="profile-card__value">{{ playerStore.name || 'Goutham 😎' }}</span>
                    </div>
                </div>
                <button type="button" class="btn-edit-name" @click="startEditing">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                </button>
            </div>

            <!-- Image Grid -->
            <div class="image-grid-redesign mb-3">
                <button
                    v-for="image in pottuStore.images"
                    :key="image.id"
                    type="button"
                    class="image-card-item"
                    :class="{ 'image-card-item--selected': pottuStore.selectedImageId === image.id }"
                    @click="selectImage(image.id)"
                >
                    <div class="image-card-item__container">
                        <img
                            :src="resolvePottuImageUrl(image)"
                            :alt="image.title || 'Girl'"
                            class="image-card-item__img"
                            loading="lazy"
                            @error="onImageError(image, $event)"
                        />
                        <div v-if="image.isUploading" class="image-card-item__loading">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </div>
                        
                        <!-- Top-left selected ribbon/badge -->
                        <div v-if="pottuStore.selectedImageId === image.id" class="image-card-item__ribbon">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            <span>Selected</span>
                        </div>

                        <div v-if="isCustomImage(image)" class="image-card-item__privacy-badge">
                            {{ customRetentionDays }}-day only
                        </div>

                        <!-- Top-right selection indicator -->
                        <div class="image-card-item__indicator" :class="{ 'image-card-item__indicator--checked': pottuStore.selectedImageId === image.id }">
                            <svg v-if="pottuStore.selectedImageId === image.id" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                    </div>
                    <span class="image-card-item__title">{{ image.title || 'Image' }}</span>
                </button>
            </div>

            <!-- Upload Custom Image -->
            <button
                type="button"
                class="upload-card-btn w-100 d-flex align-items-center justify-content-between mb-3"
                @click="$refs.fileInput.click()"
            >
                <div class="d-flex align-items-center gap-3">
                    <div class="upload-card-btn__icon-wrapper d-flex align-items-center justify-content-center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="17 8 12 3 7 8" />
                            <line x1="12" y1="3" x2="12" y2="15" />
                        </svg>
                    </div>
                    <div class="text-start">
                        <span class="upload-card-btn__title">{{ t('upload_own') }}</span>
                        <span class="upload-card-btn__subtitle">{{ t('upload_hint', { days: customRetentionDays }) }}</span>
                    </div>
                </div>
                <span class="upload-card-btn__badge">{{ t('upload_days', { days: customRetentionDays }) }}</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="upload-card-btn__chevron">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
            </button>
            <input
                type="file"
                ref="fileInput"
                class="d-none"
                accept="image/*"
                @change="handleFileUpload"
            />

            <div class="privacy-badges-row d-flex flex-wrap justify-content-center gap-2 mb-3">
                <div class="badge-tag badge-tag--privacy">
                    <svg class="badge-tag__icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <span>Not saved permanently</span>
                </div>
                <div class="badge-tag badge-tag--privacy">
                    <svg class="badge-tag__icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    <span>Auto-deleted after {{ customRetentionDays }} days</span>
                </div>
                <div class="badge-tag badge-tag--privacy">
                    <svg class="badge-tag__icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                    <span>Challenge valid {{ customChallengeDays }} days</span>
                </div>
            </div>

            <p class="privacy-notice text-center mb-4">
                {{ customPrivacyNotice }}
            </p>

            <!-- Tips Card -->
            <div class="tips-card mb-5 d-flex align-items-stretch">
                <div class="tips-card__content d-flex align-items-start gap-3 flex-grow-1">
                    <div class="tips-card__icon-wrapper d-flex align-items-center justify-content-center flex-shrink-0">
                        <svg class="tips-card__bulb" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M9 18h6" />
                            <path d="M10 22h4" />
                            <path d="M12 2a7 7 0 0 0-7 7c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74a7 7 0 0 0-7-7z" />
                        </svg>
                    </div>
                    <div class="tips-card__body">
                        <h4 class="tips-card__title">{{ t('tips_title') }}</h4>
                        <ul class="tips-card__list list-unstyled mb-0">
                            <li>
                                <svg class="tips-card__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>{{ t('tip_front') }}</span>
                            </li>
                            <li>
                                <svg class="tips-card__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>{{ t('tip_forehead') }}</span>
                            </li>
                            <li>
                                <svg class="tips-card__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>{{ t('tip_lighting') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Tips Cartoon Girl Illustration -->
                <div class="tips-card__illustration flex-shrink-0">
                    <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Hair bun -->
                        <circle cx="60" cy="35" r="28" fill="#1e1b4b"/>
                        <circle cx="60" cy="35" r="25" fill="#111827"/>
                        <!-- Flowers (Jasmine garland) -->
                        <path d="M32,35 C32,20 88,20 88,35" stroke="#fef08a" stroke-width="8" stroke-linecap="round"/>
                        <path d="M32,35 C32,20 88,20 88,35" stroke="#ffffff" stroke-width="6" stroke-linecap="round" stroke-dasharray="4,6"/>
                        <!-- Neck -->
                        <rect x="52" y="75" width="16" height="20" fill="#fde047"/>
                        <!-- Face -->
                        <path d="M35,50 C35,32 85,32 85,50 C85,68 78,82 60,82 C42,82 35,68 35,50 Z" fill="#fef08a"/>
                        <!-- Hair front -->
                        <path d="M35,46 C48,32 72,32 85,46 C80,36 70,36 60,42 C50,36 40,36 35,46 Z" fill="#111827"/>
                        <!-- Ear ornaments (Jhumkas) -->
                        <circle cx="32" cy="62" r="5" fill="#eab308"/>
                        <path d="M30,65 L34,65 L32,70 Z" fill="#ca8a04"/>
                        <circle cx="88" cy="62" r="5" fill="#eab308"/>
                        <path d="M86,65 L90,65 L88,70 Z" fill="#ca8a04"/>
                        <!-- Eyebrows -->
                        <path d="M42,50 C46,47 51,48 53,51" stroke="#111827" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M78,50 C74,47 69,48 67,51" stroke="#111827" stroke-width="1.8" stroke-linecap="round"/>
                        <!-- Pottu (Red dot) -->
                        <circle cx="60" cy="46" r="3.5" fill="#dc2626"/>
                        <!-- Eyes (closed/smiling) -->
                        <path d="M43,56 C46,59 50,59 52,56" stroke="#111827" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                        <path d="M77,56 C74,59 70,59 68,56" stroke="#111827" stroke-width="1.8" stroke-linecap="round" fill="none"/>
                        <!-- Nose -->
                        <path d="M60,54 L58,62 L62,62 Z" fill="#ca8a04" opacity="0.3"/>
                        <!-- Lips -->
                        <path d="M52,69 C55,73 65,73 68,69" fill="#dc2626"/>
                        <path d="M52,69 C55,71 65,71 68,69" stroke="#b91c1c" stroke-width="0.8" fill="none"/>
                        <!-- Necklace -->
                        <path d="M46,80 C50,86 70,86 74,80" stroke="#eab308" stroke-width="3" stroke-linecap="round" fill="none"/>
                        <path d="M46,80 C50,86 70,86 74,80" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-dasharray="2,4" fill="none"/>
                    </svg>
                </div>
            </div>
        </template>

        <!-- Step 2/Placement Screen -->
        <template v-else-if="pottuStore.selectedImage">
            <button
                v-if="isCreator && !showChallengerResult"
                type="button"
                class="btn-back-image mb-3"
                @click="step = 'image'"
            >
                {{ t('change_image') }}
            </button>
            <p v-if="isCreator && !pottuStore.placement && !showChallengerResult" class="placement-hint text-center mb-3">
                {{ t('placement_hint') }}
            </p>
            <div v-if="isChallenger && pottuStore.challengeMeta && !showChallengerResult" class="challenge-banner glass-card p-3 mb-3">
                <p class="challenge-banner__title mb-1">
                    {{ t('challenger_banner', { name: pottuStore.challengeMeta.creator_name || t('your_friend') }) }}
                </p>
                <p class="challenge-banner__subtitle small mb-0">
                    {{ t('challenger_timer_hint') }}
                </p>
            </div>

            <PottuChallenge
                :image="pottuStore.selectedImage"
                :settings="{
                    ...pottuStore.settings,
                    time_limit_sec: pottuStore.settings.time_limit_sec ?? 30,
                    creator_target: pottuStore.creatorTarget,
                    reference_size: pottuStore.referenceSize,
                }"
                :role="pottuStore.role"
                :placement="pottuStore.placement"
                :show-result="showChallengerResult"
                :creator-position="creatorPosition"
                :friend-position="friendPosition"
                :result-label="resultLabel"
                :accuracy-percent="accuracyPercent"
                :pixel-distance="pixelDistance"
                :session-key="sessionStore.sessionId"
                @update:placement="onPlacement"
                @guess-placed="onChallengerGuess"
                @time-expired="onTimeExpired"
            />
        </template>

        <div v-else class="glass-card p-4 text-center pottu-empty-state">
            {{ t('no_images') }}
        </div>

        <!-- How to Play Bottom Sheet -->
        <div v-if="showHowTo" class="sheet-backdrop" @click.self="showHowTo = false">
            <div class="sheet">
                <div class="sheet__handle"></div>
                <h2>{{ t('how_to_play') }} 🎯</h2>
                <ul class="howto-list">
                    <li v-for="(item, idx) in howToSteps" :key="idx">
                        <span class="howto-list__num">{{ idx + 1 }}</span>
                        <div class="howto-list__body">
                            <strong>{{ item.title }}</strong>
                            <p>{{ item.text }}</p>
                        </div>
                    </li>
                </ul>
                <button type="button" class="sheet__close" @click="showHowTo = false">{{ t('got_it') }}</button>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showStickyCta" class="sticky-cta-pottu">
                <button
                    v-if="showImageStepCta"
                    type="button"
                    class="cta-btn-pottu w-100"
                    @click="continueToSetPottu"
                >
                    <span>{{ t('continue_set_pottu') }}</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </button>

                <button
                    v-else-if="showCreatorConfirmCta"
                    type="button"
                    class="cta-btn-pottu w-100"
                    :disabled="submitting"
                    @click="confirmChallenge"
                >
                    <span>{{ t('confirm_challenge') }}</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                </button>

                <div v-else-if="showChallengerResultCta" class="d-grid gap-2 w-100">
                    <button
                        v-if="challengerResult?.can_create_next_challenge"
                        type="button"
                        class="cta-btn-pottu w-100"
                        @click="createNextChallenge"
                    >
                        <span>{{ t('create_challenge') }}</span>
                    </button>
                    <button type="button" class="btn-secondary-pottu w-100" @click="goToFullResult">
                        <span>{{ t('view_full_result') }}</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.game-engine {
    padding-bottom: calc(6rem + env(safe-area-inset-bottom, 0px));
}

@media (min-width: 768px) {
    .game-engine {
        padding-bottom: 5.5rem;
    }
}

/* Custom Styles for Redesigned Image Picker */
.topbar-pottu {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: 60px;
}

.brand {
    display: flex;
    align-items: center;
    flex-shrink: 1;
    min-width: 0;
}

.brand__logo-img {
    height: clamp(52px, 16vw, 85px);
    max-width: min(70vw, 280px);
    width: auto;
    object-fit: contain;
    filter: drop-shadow(0 4px 14px rgba(0, 0, 0, 0.18));
}

.btn-help {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 40px;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    border: 1.5px solid #c7d2fe;
    background: #ffffffb3;
    color: #ea580c;
    font-size: 0.78rem;
    font-weight: 800;
    cursor: pointer;
}

.btn-help__icon {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 1.5px solid currentColor;
    display: grid;
    place-items: center;
    font-size: 0.7rem;
    font-weight: 900;
}

.stepper-pottu {
    text-align: center;
}

.stepper-pottu__track {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
}

.stepper-pottu__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    width: 72px;
}

.stepper-pottu__dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #c7d2fe;
    background: transparent;
    color: #9ca3af;
    font-weight: 700;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.stepper-pottu__item--active .stepper-pottu__dot {
    border-width: 3px;
    border-color: #ea580c;
    box-shadow: inset 0 0 0 4px #fffaf5, inset 0 0 0 8px #ea580c;
    color: transparent;
    background: #ea580c;
}

.stepper-pottu__dot--icon {
    color: #9ca3af;
}

.stepper-pottu__item--active .stepper-pottu__dot--icon {
    color: #ffffff;
    box-shadow: none;
    background: #ea580c;
    border-color: #ea580c;
}

.stepper-pottu__text {
    font-size: 0.65rem;
    font-weight: 700;
    color: #9ca3af;
    white-space: nowrap;
    line-height: 1.2;
}

.stepper-pottu__item--active .stepper-pottu__text {
    color: #1f2937;
}

.stepper-pottu__line {
    flex: 1;
    max-width: 48px;
    height: 2px;
    background: #c7d2fe;
    margin-bottom: 0.95rem;
}

.stepper-pottu__line--active {
    background: #ea580c;
}

.choose-title {
    margin: 0 0 0.3rem;
    font-size: clamp(1.45rem, 5.5vw, 1.75rem);
    font-weight: 900;
    color: #1f2937;
    line-height: 1.15;
}

.choose-subtitle {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 600;
    color: #6b7280;
}

.badges-row {
    flex-wrap: wrap;
}

.badge-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: #ffffffd6;
    border: 1px solid rgba(229, 231, 235, 0.5);
    color: #4b5563;
    font-size: 0.7rem;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}

.badge-tag--hd {
    border-color: rgba(236, 72, 153, 0.2);
}

.badge-tag__icon--hd {
    background: #8b5cf6;
    color: #ffffff;
    font-size: 0.55rem;
    font-weight: 900;
    padding: 0.1rem 0.25rem;
    border-radius: 4px;
}

.badge-tag__icon-svg {
    width: 14px;
    height: 14px;
    color: #6b7280;
}

.badge-tag--privacy {
    border-color: rgba(59, 130, 246, 0.22);
    background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(253, 242, 248, 0.95));
    color: #1d4ed8;
}

.badge-tag--privacy .badge-tag__icon-svg {
    color: #2563eb;
}

.privacy-badges-row {
    padding: 0 0.15rem;
}

.privacy-notice {
    margin: 0;
    font-size: 0.72rem;
    line-height: 1.45;
    font-weight: 600;
    color: #64748b;
}

.profile-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 0.65rem 0.85rem;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
}

.profile-card__avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #ea580c;
}

.profile-card__info {
    display: flex;
    flex-direction: column;
}

.profile-card__label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #9ca3af;
}

.profile-card__value {
    font-size: 0.95rem;
    font-weight: 800;
    color: #1f2937;
}

.name-edit-input {
    font-size: 0.9rem;
    font-weight: 800;
    color: #1f2937;
    padding: 0.15rem 0.5rem;
    border-radius: 6px;
    border: 1.5px solid #c7d2fe;
    height: 32px;
}

.btn-edit-name {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e0e7ff;
    border: 0;
    color: #ea580c;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.btn-edit-name:active {
    transform: scale(0.92);
}

.image-grid-redesign {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.image-card-item {
    border: 0;
    padding: 0;
    background: transparent;
    cursor: pointer;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.image-card-item__container {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 3;
    border-radius: 20px;
    overflow: hidden;
    background: #f3f4f6;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.image-card-item--selected .image-card-item__container {
    border: 2px solid #6366f1;
    box-shadow:
        0 0 0 2px rgba(236, 72, 153, 0.35),
        0 10px 24px rgba(236, 72, 153, 0.22);
}

.image-card-item__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    background: #f3f4f6;
}

.image-card-item__loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.72);
    z-index: 3;
}

.image-card-item__ribbon {
    position: absolute;
    top: 0;
    left: 0;
    background: #8b5cf6;
    color: #ffffff;
    font-size: 0.6rem;
    font-weight: 800;
    padding: 0.2rem 0.5rem;
    border-bottom-right-radius: 12px;
    display: flex;
    align-items: center;
    z-index: 2;
}

.image-card-item__indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    background: rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    z-index: 2;
}

.image-card-item__indicator--checked {
    background: #6366f1;
    border-color: #6366f1;
}

.image-card-item__title {
    font-size: 0.85rem;
    font-weight: 800;
    color: #1f2937;
    text-align: center;
    width: 100%;
}

.image-card-item__privacy-badge {
    position: absolute;
    bottom: 8px;
    left: 8px;
    z-index: 2;
    padding: 0.18rem 0.45rem;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.92);
    color: #ffffff;
    font-size: 0.58rem;
    font-weight: 800;
    letter-spacing: 0.01em;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}

.upload-card-btn {
    background: #ffffff;
    border: 2px dashed #fbcfe8;
    border-radius: 18px;
    padding: 0.65rem 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.upload-card-btn:hover {
    background: #fdf2f8;
    border-color: #f472b6;
}

.upload-card-btn__icon-wrapper {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #fdf2f8;
    color: #db2777;
}

.upload-card-btn__title {
    display: block;
    font-size: 0.88rem;
    font-weight: 800;
    color: #1f2937;
}

.upload-card-btn__subtitle {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    color: #9ca3af;
}

.upload-card-btn__badge {
    flex-shrink: 0;
    margin-right: 0.35rem;
    padding: 0.22rem 0.55rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #dbeafe, #fce7f3);
    border: 1px solid rgba(59, 130, 246, 0.2);
    color: #1d4ed8;
    font-size: 0.68rem;
    font-weight: 800;
    white-space: nowrap;
}

.upload-card-btn__chevron {
    color: #f472b6;
}

.tips-card {
    background: #fffbeb;
    border: 1.5px solid #fef08a;
    border-radius: 20px;
    padding: 1rem;
    position: relative;
    overflow: hidden;
}

.tips-card__icon-wrapper {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #ffffff;
    color: #eab308;
    box-shadow: 0 4px 10px rgba(234, 179, 8, 0.15);
}

.tips-card__bulb {
    width: 20px;
    height: 20px;
}

.tips-card__title {
    margin: 0 0 0.35rem;
    font-size: 0.88rem;
    font-weight: 800;
    color: #1f2937;
}

.tips-card__list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.tips-card__list li {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #4b5563;
}

.tips-card__check {
    width: 12px;
    height: 12px;
    color: #10b981;
}

.tips-card__illustration {
    width: 72px;
    height: 72px;
    margin-top: -0.25rem;
    margin-right: -0.25rem;
    align-self: flex-end;
}

.btn-back-image {
    border: 0;
    background: #ffffff;
    color: #ea580c;
    font-size: 0.85rem;
    font-weight: 800;
    padding: 0.45rem 0.85rem;
    border-radius: 999px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    cursor: pointer;
}

.placement-hint {
    margin: 0;
    padding: 0.55rem 0.85rem;
    border-radius: 999px;
    background: rgba(255, 237, 213, 0.9);
    color: #c2410c;
    font-size: 0.8rem;
    font-weight: 800;
}

.sticky-cta-pottu {
    position: fixed;
    left: 50%;
    right: auto;
    bottom: 0;
    z-index: 90;
    width: min(640px, 100%);
    transform: translateX(-50%);
    margin: 0;
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

.cta-btn-pottu {
    width: 100%;
    min-height: 50px;
    border: 0;
    border-radius: 999px;
    padding: 0.8rem 1.2rem;
    background: linear-gradient(90deg, #6366f1 0%, #8b5cf6 100%);
    color: #ffffff;
    font-size: 0.95rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 10px 24px rgba(236, 72, 153, 0.35);
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.cta-btn-pottu:active {
    transform: scale(0.985);
}

.cta-btn-pottu:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.btn-secondary-pottu {
    width: 100%;
    min-height: 50px;
    border: 2px solid #6366f1;
    border-radius: 999px;
    padding: 0.8rem 1.2rem;
    background: #ffffff;
    color: #4f46e5;
    font-size: 0.95rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 4px 16px rgba(99, 102, 241, 0.15);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-secondary-pottu:hover,
.btn-secondary-pottu:focus {
    background: #6366f1;
    color: #ffffff;
    border-color: #6366f1;
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.35);
}

.btn-secondary-pottu:active {
    transform: scale(0.985);
}

/* How-to sheet */
.sheet-backdrop {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    animation: fade-in 0.2s ease;
}

.sheet {
    width: min(640px, 100%);
    background: #fff;
    border-radius: 24px 24px 0 0;
    padding: 0.75rem 1.25rem max(1.25rem, env(safe-area-inset-bottom));
    box-shadow: 0 -12px 40px rgba(0, 0, 0, 0.15);
    animation: slide-up 0.25s ease;
}

.sheet__handle {
    width: 40px;
    height: 4px;
    border-radius: 999px;
    background: #e5e7eb;
    margin: 0 auto 1rem;
}

.sheet h2 {
    margin: 0 0 1rem;
    font-size: 1.2rem;
    font-weight: 900;
    color: #1f2937;
    text-align: center;
}

.howto-list {
    list-style: none;
    margin: 0 0 1.25rem;
    padding: 0;
    display: grid;
    gap: 0.85rem;
}

.howto-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
}

.howto-list__num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #e0e7ff;
    color: #ea580c;
    font-size: 0.7rem;
    font-weight: 900;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    margin-top: 2px;
}

.howto-list strong {
    display: block;
    font-size: 0.92rem;
    color: #1f2937;
}

.howto-list p {
    margin: 0.1rem 0 0;
    font-size: 0.78rem;
    color: #6b7280;
    font-weight: 600;
}

.sheet__close {
    width: 100%;
    min-height: 48px;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
}

.challenge-banner p {
    margin: 0;
}

.challenge-banner__title {
    color: #1f2937;
    font-weight: 700;
}

.challenge-banner__subtitle {
    color: #6b7280;
}

.pottu-empty-state {
    color: #6b7280;
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slide-up {
    from { transform: translateY(24px); opacity: 0.6; }
    to { transform: translateY(0); opacity: 1; }
}

@media (max-width: 480px) {
    .image-grid-redesign {
        gap: 0.65rem;
    }
}
</style>
