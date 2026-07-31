import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import * as campaignsApi from '@/api/campaigns';

export const useCampaignStore = defineStore('campaign', () => {
    const campaign = ref(null);
    const campaigns = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const theme = computed(() => campaign.value?.theme ?? null);

    const themeCssVars = computed(() => {
        const t = theme.value;
        if (!t) {
            return {
                '--primary': '#6366f1',
                '--secondary': '#64748b',
                '--accent': '#818cf8',
                '--bg-gradient': '#0f172a',
                '--font-family': "'Baloo 2', 'Segoe UI', sans-serif",
            };
        }

        return {
            '--primary': t.primary_color || '#6366f1',
            '--secondary': t.secondary_color || '#64748b',
            '--accent': t.accent_color || '#818cf8',
            '--bg-gradient': t.background_gradient || '#0f172a',
            '--font-family': `'${t.font_family || 'Baloo 2'}', 'Segoe UI', sans-serif`,
            '--bg-image': t.background_image ? `url(${t.background_image})` : 'none',
        };
    });

    const slug = computed(() => campaign.value?.slug ?? null);
    const shareMessage = computed(() => campaign.value?.share_message ?? '');

    async function fetchActive() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await campaignsApi.getActive();
            const list = data.data ?? data;
            campaigns.value = Array.isArray(list) ? list : [list];
            campaign.value = campaigns.value.find((c) => c.is_featured) ?? campaigns.value[0] ?? null;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to load campaigns';
        } finally {
            loading.value = false;
        }
    }

    async function fetchFeatured() {
        await fetchActive();
    }

    async function fetchBySlug(campaignSlug) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await campaignsApi.getBySlug(campaignSlug);
            campaign.value = data.data ?? data.campaign ?? data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Campaign not found';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        campaign,
        campaigns,
        loading,
        error,
        theme,
        themeCssVars,
        slug,
        shareMessage,
        fetchActive,
        fetchFeatured,
        fetchBySlug,
    };
});
