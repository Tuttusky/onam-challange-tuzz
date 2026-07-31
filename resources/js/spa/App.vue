<script setup>
import { computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useCampaignStore } from '@/stores/campaign';
import { useUiStore } from '@/stores/ui';
import { useTheme } from '@/composables/useTheme';
import LoadingOverlay from '@/components/LoadingOverlay.vue';

const route = useRoute();
const campaignStore = useCampaignStore();
const uiStore = useUiStore();
const { applyTheme } = useTheme();

const themeStyle = computed(() => campaignStore.themeCssVars);

onMounted(async () => {
    await campaignStore.fetchFeatured();
    applyTheme(campaignStore.theme);
});

watch(
    () => campaignStore.theme,
    (theme) => applyTheme(theme),
    { deep: true }
);

watch(
    () => route.params.slug,
    async (slug) => {
        if (slug && route.name !== 'cms') {
            await campaignStore.fetchBySlug(slug);
        }
    }
);
</script>

<template>
    <div
        id="spa-root"
        class="spa-root"
        :class="{ 'spa-root--dark': uiStore.darkMode }"
        :style="themeStyle"
    >
        <LoadingOverlay v-if="uiStore.loading" />
        <router-view v-slot="{ Component, route: currentRoute }">
            <transition name="page" mode="out-in">
                <component :is="Component" :key="currentRoute.fullPath" />
            </transition>
        </router-view>
    </div>
</template>
