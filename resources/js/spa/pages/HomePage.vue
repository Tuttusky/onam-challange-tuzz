<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useCampaignStore } from '@/stores/campaign';
import { usePlayerStore } from '@/stores/player';
import { useLocaleStore } from '@/stores/locale';
import { useUiStore } from '@/stores/ui';
import { useSound } from '@/composables/useSound';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';
import PottuFlowStepper from '@/components/pottu/PottuFlowStepper.vue';
import SocialProofToast from '@/components/SocialProofToast.vue';

const POTTU_SLUG = 'sundarikk-pottu-thodal';

const router = useRouter();
const campaignStore = useCampaignStore();
const playerStore = usePlayerStore();
const localeStore = useLocaleStore();
const uiStore = useUiStore();
const { tap } = useSound();
const { t, setLocale } = usePottuFlowI18n();

const name = ref(playerStore.name || '');
const homeStep = ref(1);
const selectedLanguage = ref(localeStore.locale || '');
const nameInputRef = ref(null);
const error = ref(null);
const showHowTo = ref(false);
const submitting = ref(false);

const languageOptions = [
    { code: 'en', labelKey: 'lang_english', native: 'English' },
    { code: 'ml', labelKey: 'lang_malayalam', native: 'മലയാളം' },
];

const socialProofGames = computed(() => [
    {
        slug: POTTU_SLUG,
        name: t('hero_title'),
        stat: '3.2K',
    },
]);

const howToSteps = computed(() => [
    { icon: '🎯', title: t('howto_pick_game'), text: t('howto_pick_game_text') },
    { icon: '📍', title: t('howto_place_pottu'), text: t('howto_place_pottu_text') },
    { icon: '📤', title: t('howto_share'), text: t('howto_share_text') },
]);

const canContinueName = computed(() => Boolean(name.value.trim()) && !submitting.value);
const canStartChallenge = computed(
    () => Boolean(name.value.trim()) && Boolean(selectedLanguage.value) && !submitting.value
);

onMounted(async () => {
    uiStore.setLoading(true);
    try {
        await campaignStore.fetchActive();
    } finally {
        uiStore.setLoading(false);
    }
});

async function continueToLanguage() {
    if (!name.value.trim()) {
        error.value = t('error_name_required');
        await nextTick();
        nameInputRef.value?.focus();
        return;
    }

    error.value = null;
    tap();
    homeStep.value = 2;
}

async function startChallenge() {
    if (!name.value.trim()) {
        error.value = t('error_name_required');
        homeStep.value = 1;
        return;
    }

    if (!selectedLanguage.value) {
        error.value = t('error_language_required');
        return;
    }

    error.value = null;
    submitting.value = true;
    tap();
    playerStore.setName(name.value.trim());
    setLocale(selectedLanguage.value);

    try {
        await router.push({
            name: 'play',
            params: { slug: POTTU_SLUG },
            query: {
                name: name.value.trim(),
                lang: selectedLanguage.value,
            },
        });
    } finally {
        submitting.value = false;
    }
}

function selectLanguage(code) {
    selectedLanguage.value = code;
    error.value = null;
    tap();
}

function goBackToName() {
    homeStep.value = 1;
    error.value = null;
    tap();
}

function onCtaClick() {
    if (homeStep.value === 1) {
        continueToLanguage();
        return;
    }

    startChallenge();
}

function openHowTo() {
    tap();
    showHowTo.value = true;
}

function closeHowTo() {
    showHowTo.value = false;
}

function clearError() {
    if (error.value) {
        error.value = null;
    }
}
</script>

<template>
    <div class="home">
        <div class="home__bg" aria-hidden="true">
            <div class="home__glow home__glow--a"></div>
            <div class="home__glow home__glow--b"></div>
            <span class="home__petal home__petal--1">🌸</span>
            <span class="home__petal home__petal--2">🌼</span>
        </div>

        <main class="home__main">
            <header class="topbar">
                <div class="brand" aria-label="Sundarikk Pottuthodal">
                    <img src="/images/logo.png" alt="Sundarikk Pottuthodal" class="brand__logo-img" />
                </div>
                <button type="button" class="btn-help" aria-haspopup="dialog" @click="openHowTo">
                    <span class="btn-help__icon" aria-hidden="true">?</span>
                    <span class="btn-help__text">{{ t('how_to_play') }}</span>
                </button>
            </header>

            <PottuFlowStepper :current-step="homeStep" />

            <section class="hero">
                <h1 class="hero__title">
                    {{ t('hero_title') }}
                    <span aria-hidden="true">🌸</span>
                </h1>
                <p class="hero__sub">{{ t('hero_sub') }}</p>
            </section>

            <section v-if="homeStep === 1" class="panel name-panel">
                <div class="name-field" :class="{ 'name-field--error': error && !name.trim() }">
                    <div class="name-field__avatar" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                    <div class="name-field__body">
                        <label for="player-name">{{ t('your_name') }}</label>
                        <input
                            id="player-name"
                            ref="nameInputRef"
                            v-model="name"
                            type="text"
                            enterkeyhint="done"
                            autocomplete="name"
                            autocapitalize="words"
                            :placeholder="t('enter_name')"
                            maxlength="50"
                            @input="clearError"
                            @keyup.enter="continueToLanguage"
                        />
                    </div>
                </div>
            </section>

            <section v-else class="panel language-panel">
                <div class="section-head">
                    <h2>{{ t('choose_language') }}</h2>
                    <p>{{ t('choose_language_sub') }}</p>
                </div>

                <div class="language-list" role="radiogroup" :aria-label="t('choose_language')">
                    <button
                        v-for="option in languageOptions"
                        :key="option.code"
                        type="button"
                        role="radio"
                        class="language-card"
                        :class="{ 'language-card--selected': selectedLanguage === option.code }"
                        :aria-checked="selectedLanguage === option.code"
                        @click="selectLanguage(option.code)"
                    >
                        <span class="language-card__label">{{ t(option.labelKey) }}</span>
                        <span
                            v-if="selectedLanguage === option.code"
                            class="language-card__check"
                            aria-hidden="true"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </span>
                    </button>
                </div>

                <button type="button" class="btn-back-step" @click="goBackToName">
                    ← {{ t('your_name') }}
                </button>
            </section>

            <p v-if="error" class="home__error" role="alert">{{ error }}</p>
        </main>

        <SocialProofToast :games="socialProofGames" :paused="showHowTo" />

        <!-- Sticky CTA above bottom nav -->
        <div class="sticky-cta safe-bottom">
            <button
                type="button"
                class="cta"
                :disabled="submitting || (homeStep === 1 ? !canContinueName : !canStartChallenge)"
                :aria-busy="submitting"
                @click="onCtaClick"
            >
                <template v-if="submitting">{{ t('starting') }}</template>
                <template v-else-if="homeStep === 1">
                    {{ t('continue_btn') }}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </template>
                <template v-else>
                    {{ t('start_challenge') }}
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </template>
            </button>
        </div>

        <nav class="bottom-nav safe-bottom" aria-label="Main">
            <router-link to="/" class="bottom-nav__item bottom-nav__item--active" aria-current="page">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                <span>{{ t('home_nav') }}</span>
            </router-link>
            <router-link
                :to="{ name: 'leaderboard', query: { campaign: POTTU_SLUG } }"
                class="bottom-nav__item"
            >
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 21h8" />
                    <path d="M12 17v4" />
                    <path d="M7 4h10" />
                    <path d="M12 4v13" />
                    <path d="M17 4v4a5 5 0 0 1-10 0V4" />
                </svg>
                <span>{{ t('leaderboard_nav') }}</span>
            </router-link>
            <button type="button" class="bottom-nav__item" disabled aria-disabled="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                <span>Profile</span>
            </button>
        </nav>

        <!-- How to Play sheet -->
        <Teleport to="body">
            <div
                v-if="showHowTo"
                class="sheet-backdrop"
                role="presentation"
                @click.self="closeHowTo"
            >
                <div
                    class="sheet safe-bottom"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="howto-title"
                >
                    <div class="sheet__handle" aria-hidden="true"></div>
                    <h2 id="howto-title">{{ t('how_to_play') }}</h2>
                    <ul class="howto-list">
                        <li v-for="(step, i) in howToSteps" :key="step.title">
                            <span class="howto-list__num">{{ i + 1 }}</span>
                            <span class="howto-list__icon" aria-hidden="true">{{ step.icon }}</span>
                            <div>
                                <strong>{{ step.title }}</strong>
                                <p>{{ step.text }}</p>
                            </div>
                        </li>
                    </ul>
                    <button type="button" class="sheet__close" @click="closeHowTo">{{ t('got_it') }}</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.home {
    --orange: #6366f1;
    --pink: #8b5cf6;
    --ink: #1f2937;
    --muted: #6b7280;
    --cream: #fffaf5;
    --card: #ffffff;
    --nav-h: 64px;
    --cta-h: 88px;
    --safe-bottom: env(safe-area-inset-bottom, 0px);

    position: relative;
    min-height: 100dvh;
    background: var(--cream);
    font-family: 'Baloo 2', 'Segoe UI', system-ui, sans-serif;
    padding-bottom: calc(var(--nav-h) + var(--cta-h) + var(--safe-bottom));
    overflow-x: hidden;
    -webkit-tap-highlight-color: transparent;
}

.safe-bottom {
    padding-bottom: max(0.5rem, var(--safe-bottom));
}

.home__bg {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.home__glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(56px);
    opacity: 0.45;
}

.home__glow--a {
    width: 220px;
    height: 220px;
    top: -60px;
    left: -40px;
    background: #e0e7ff;
}

.home__glow--b {
    width: 180px;
    height: 180px;
    top: 18%;
    right: -50px;
    background: #fce7f3;
}

.home__petal {
    position: absolute;
    opacity: 0.4;
    font-size: 1.25rem;
    animation: float 7s ease-in-out infinite;
}

.home__petal--1 { top: 12%; right: 8%; }
.home__petal--2 { top: 28%; left: 6%; animation-delay: -2.5s; font-size: 1rem; }

.home__main {
    position: relative;
    z-index: 1;
    width: min(480px, 100%);
    margin: 0 auto;
    padding: max(0.85rem, env(safe-area-inset-top)) 1rem 1rem;
}

/* Top bar */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
    min-height: 44px;
}

.brand {
    display: flex;
    align-items: center;
}

.brand__logo-img {
    height: 52px;
    width: auto;
    max-width: 190px;
    object-fit: contain;
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.12));
}

.btn-help {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 40px;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    border: 1.5px solid #c7d2fe;
    background: rgba(255, 255, 255, 0.7);
    color: var(--orange);
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

/* Stepper — compact */
.stepper {
    text-align: center;
    margin-bottom: 1.1rem;
}

.stepper__label {
    margin: 0 0 0.55rem;
    color: var(--orange);
    font-size: 0.8rem;
    font-weight: 800;
}

.stepper__track {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 0;
}

.stepper__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    width: 72px;
    position: relative;
}

.stepper__item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 9px;
    left: calc(50% + 12px);
    width: 48px;
    height: 2px;
    background: #c7d2fe;
}

.stepper__dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #c7d2fe;
    background: transparent;
    z-index: 1;
}

.stepper__item--active .stepper__dot {
    border-width: 3px;
    border-color: var(--orange);
    box-shadow: inset 0 0 0 4px var(--cream), inset 0 0 0 8px var(--orange);
}

.stepper__text {
    font-size: 0.65rem;
    font-weight: 700;
    color: #9ca3af;
    line-height: 1.2;
}

.stepper__item--active .stepper__text {
    color: var(--ink);
}

/* Hero */
.hero {
    text-align: center;
    margin-bottom: 1.1rem;
}

.hero__title {
    margin: 0 0 0.3rem;
    font-size: clamp(1.45rem, 5.5vw, 1.75rem);
    font-weight: 900;
    color: var(--ink);
    line-height: 1.15;
}

.hero__title em {
    font-style: normal;
    color: var(--orange);
}

.hero__sub {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--muted);
}

/* Panels */
.panel {
    margin-bottom: 1rem;
}

.name-field {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-height: 64px;
    padding: 0.65rem 0.85rem;
    background: var(--card);
    border-radius: 18px;
    border: 2px solid transparent;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    transition: border-color 0.15s ease;
}

.name-field--error {
    border-color: #fca5a5;
}

.name-field__avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e0e7ff, #ddd6fe);
    color: var(--orange);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.name-field__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.name-field__body label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #9ca3af;
}

.name-field__body input {
    border: 0;
    outline: none;
    background: transparent;
    width: 100%;
    font-size: 16px; /* prevents iOS zoom */
    font-weight: 800;
    color: var(--ink);
    padding: 0.1rem 0 0;
    font-family: inherit;
}

.name-field__body input::placeholder {
    color: #d1d5db;
    font-weight: 600;
}

.language-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.language-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    min-height: 56px;
    padding: 0.85rem 1rem;
    text-align: left;
    background: var(--card);
    border: 2px solid transparent;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    cursor: pointer;
    font-family: inherit;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.language-card--selected {
    border-color: var(--orange);
    box-shadow: 0 8px 22px rgba(99, 102, 241, 0.15);
}

.language-card__label {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--ink);
}

.language-card__check {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--orange);
    color: #fff;
    display: grid;
    place-items: center;
}

.btn-back-step {
    margin-top: 0.75rem;
    padding: 0;
    border: none;
    background: none;
    color: var(--orange);
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
}

.section-head {
    margin-bottom: 0.75rem;
}

.section-head h2 {
    margin: 0 0 0.15rem;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--ink);
}

.section-head p {
    margin: 0;
    font-size: 0.78rem;
    color: var(--muted);
    font-weight: 600;
}

/* Game cards */
.game-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.game-card {
    display: flex;
    align-items: stretch;
    gap: 0.75rem;
    width: 100%;
    min-height: 108px;
    padding: 0.65rem;
    text-align: left;
    background: var(--card);
    border: 2px solid transparent;
    border-radius: 20px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    cursor: pointer;
    font-family: inherit;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.12s ease;
}

.game-card:active:not(.game-card--soon) {
    transform: scale(0.985);
}

.game-card--selected {
    border-color: var(--pink);
    box-shadow: 0 10px 28px rgba(139, 92, 246, 0.18);
}

.game-card--soon {
    opacity: 0.72;
}

.game-card__media {
    position: relative;
    width: 88px;
    height: 88px;
    flex-shrink: 0;
    border-radius: 14px;
    overflow: hidden;
    background: #f3f4f6;
}

.game-card__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.game-card__badge {
    position: absolute;
    top: 6px;
    left: 6px;
    padding: 0.15rem 0.4rem;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.45);
    color: #fff;
    font-size: 0.6rem;
    font-weight: 800;
    backdrop-filter: blur(4px);
}

.game-card__info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-right: 0.15rem;
}

.game-card__row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
}

.game-card__info h3 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--ink);
    line-height: 1.2;
}

.game-card__check {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: var(--pink);
    color: #fff;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.game-card__info p {
    margin: 0.25rem 0 0.55rem;
    font-size: 0.72rem;
    line-height: 1.35;
    color: var(--muted);
    font-weight: 600;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.game-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.game-card__avatars {
    display: flex;
}

.game-card__avatars span {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #fff;
    margin-left: -6px;
    background: linear-gradient(135deg, #c7d2fe, #fb7185);
}

.game-card__avatars span:nth-child(2) { background: linear-gradient(135deg, #a78bfa, #60a5fa); }
.game-card__avatars span:nth-child(3) { background: linear-gradient(135deg, #34d399, #38bdf8); }
.game-card__avatars span:nth-child(4) { background: linear-gradient(135deg, #c084fc, #818cf8); }
.game-card__avatars span:first-child { margin-left: 0; }

.game-card__stat {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    line-height: 1.1;
}

.game-card__stat strong {
    font-size: 0.8rem;
    font-weight: 800;
    color: #9ca3af;
}

.game-card__stat small {
    font-size: 0.6rem;
    font-weight: 700;
    color: #9ca3af;
}

.game-card__stat--hot strong,
.game-card__stat--hot small {
    color: var(--pink);
}

.home__error {
    margin: 0.25rem 0 0;
    padding: 0.7rem 0.85rem;
    border-radius: 12px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    font-size: 0.82rem;
    font-weight: 700;
    text-align: center;
}

/* Sticky CTA */
.sticky-cta {
    position: fixed;
    left: 0;
    right: 0;
    bottom: calc(var(--nav-h) + var(--safe-bottom));
    z-index: 40;
    width: min(480px, 100%);
    margin: 0 auto;
    padding: 0.65rem 1rem 0.5rem;
    background: linear-gradient(180deg, rgba(255, 250, 245, 0) 0%, var(--cream) 28%);
}

.cta {
    width: 100%;
    min-height: 52px;
    border: 0;
    border-radius: 999px;
    padding: 0.9rem 1.1rem;
    background: linear-gradient(90deg, var(--orange) 0%, var(--pink) 100%);
    color: #fff;
    font-size: 1rem;
    font-weight: 800;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    box-shadow: 0 10px 28px rgba(139, 92, 246, 0.32);
    cursor: pointer;
}

.cta:active {
    transform: scale(0.98);
}

.cta:disabled {
    opacity: 0.75;
    cursor: wait;
}

.sticky-cta__hint {
    margin: 0.35rem 0 0;
    text-align: center;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--muted);
}

.sticky-cta__hint strong {
    color: var(--orange);
}

/* Bottom nav */
.bottom-nav {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    height: calc(var(--nav-h) + var(--safe-bottom));
    padding-bottom: var(--safe-bottom);
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(12px);
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 -6px 24px rgba(15, 23, 42, 0.05);
}

.bottom-nav__item {
    flex: 1;
    min-height: 48px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.15rem;
    color: #9ca3af;
    text-decoration: none;
    background: none;
    border: 0;
    font-family: inherit;
    cursor: pointer;
}

.bottom-nav__item span {
    font-size: 0.65rem;
    font-weight: 700;
}

.bottom-nav__item--active {
    color: var(--orange);
}

.bottom-nav__item:disabled {
    opacity: 0.45;
    cursor: not-allowed;
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
    width: min(480px, 100%);
    background: #fff;
    border-radius: 24px 24px 0 0;
    padding: 0.75rem 1.25rem 1.25rem;
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
    color: var(--ink);
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
    color: var(--orange);
    font-size: 0.7rem;
    font-weight: 900;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    margin-top: 2px;
}

.howto-list__icon {
    font-size: 1.1rem;
    flex-shrink: 0;
}

.howto-list strong {
    display: block;
    font-size: 0.92rem;
    color: var(--ink);
}

.howto-list p {
    margin: 0.1rem 0 0;
    font-size: 0.78rem;
    color: var(--muted);
    font-weight: 600;
}

.sheet__close {
    width: 100%;
    min-height: 48px;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--orange), var(--pink));
    color: #fff;
    font-size: 1rem;
    font-weight: 800;
    font-family: inherit;
    cursor: pointer;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slide-up {
    from { transform: translateY(24px); opacity: 0.6; }
    to { transform: translateY(0); opacity: 1; }
}

@media (min-width: 640px) {
    .home__main {
        padding-top: 1.5rem;
    }

    .game-card__media {
        width: 96px;
        height: 96px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .home__petal,
    .sheet-backdrop,
    .sheet,
    .game-card,
    .cta {
        animation: none !important;
        transition: none !important;
    }
}
</style>
