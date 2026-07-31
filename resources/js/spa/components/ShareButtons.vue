<script setup>
import { computed } from 'vue';
import { useCampaignStore } from '@/stores/campaign';
import * as challengesApi from '@/api/challenges';
import { buildChallengeUrl } from '@/utils/challengeToken';

const props = defineProps({
    token: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
    challengeTitle: {
        type: String,
        default: '',
    },
});

const campaignStore = useCampaignStore();

const shareUrl = computed(() => buildChallengeUrl(props.token));

const shareText = computed(() => {
    const base = props.message || props.challengeTitle || campaignStore.shareMessage || 'Take my challenge!';
    return `${base}\n${shareUrl.value}`;
});

async function trackShare(channel) {
    try {
        await challengesApi.recordShare(props.token, { channel });
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
        `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(props.message || props.challengeTitle || 'Challenge!')}`,
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
    } catch {
        // Clipboard may be unavailable
    }
}
</script>

<template>
    <div class="share-buttons">
        <button type="button" class="btn btn-whatsapp w-100 mb-2" @click="shareWhatsApp">
            Share on WhatsApp
        </button>
        <div class="share-grid mb-2">
            <button type="button" class="btn btn-glass" @click="shareTelegram">Telegram</button>
            <button type="button" class="btn btn-glass" @click="shareFacebook">Facebook</button>
            <button type="button" class="btn btn-glass" @click="shareInstagram">Instagram</button>
        </div>
        <div class="share-buttons__link input-group">
            <input type="text" class="form-control glass-input" :value="shareUrl" readonly />
            <button type="button" class="btn btn-glass" @click="copyLink">Copy Link</button>
        </div>
    </div>
</template>

<style scoped>
.btn-whatsapp {
    background: #25d366;
    border: none;
    color: #fff;
    font-weight: 700;
    padding: 0.85rem 1rem;
    border-radius: 999px;
}

.share-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
}

.share-buttons__link .form-control {
    font-size: 0.85rem;
}
</style>
