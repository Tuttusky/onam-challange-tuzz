<script setup>
import ShareButtons from '@/components/ShareButtons.vue';

defineProps({
    token: { type: String, required: true },
    message: { type: String, default: '' },
    title: { type: String, default: '' },
});
</script>

<template>
    <div class="challenge-share glass-card p-4">
        <h3 class="text-white h6 mb-2">{{ title || 'Challenge Ready!' }}</h3>
        <p class="text-white-50 small mb-3">
            Share your secret pottu challenge. Your friend won't see where you placed it until they submit.
        </p>
        <ShareButtons :token="token" :message="message" :challenge-title="title" />
    </div>
</template>
