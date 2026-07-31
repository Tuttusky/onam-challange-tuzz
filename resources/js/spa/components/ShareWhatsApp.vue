<script setup>
import { computed } from 'vue';
import { useCampaignStore } from '@/stores/campaign';
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
    label: {
        type: String,
        default: 'Share on WhatsApp',
    },
});

const campaignStore = useCampaignStore();

const shareUrl = computed(() => buildChallengeUrl(props.token));

const shareText = computed(() => {
    const base = props.message || campaignStore.shareMessage || 'Take my dare challenge!';
    return encodeURIComponent(`${base}\n${shareUrl.value}`);
});

function shareWhatsApp() {
    window.open(`https://wa.me/?text=${shareText.value}`, '_blank', 'noopener,noreferrer');
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(shareUrl.value);
    } catch {
        // Clipboard may be unavailable
    }
}
</script>

<template>
    <div class="share-whatsapp">
        <button type="button" class="btn btn-whatsapp w-100 mb-2" @click="shareWhatsApp">
            <span class="me-2">💬</span>{{ label }}
        </button>
        <div class="share-whatsapp__link input-group">
            <input type="text" class="form-control glass-input" :value="shareUrl" readonly />
            <button type="button" class="btn btn-glass" @click="copyLink">Copy</button>
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
    box-shadow: 0 8px 24px rgba(37, 211, 102, 0.35);
}

.btn-whatsapp:hover {
    background: #1ebe57;
    color: #fff;
}

.share-whatsapp__link .form-control {
    font-size: 0.85rem;
}
</style>
