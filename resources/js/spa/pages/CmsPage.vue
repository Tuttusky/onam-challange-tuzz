<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import GlassLayout from '@/components/GlassLayout.vue';
import { useUiStore } from '@/stores/ui';
import * as cmsApi from '@/api/cms';

const route = useRoute();
const uiStore = useUiStore();

const page = ref(null);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
    uiStore.setLoading(true);
    try {
        const { data } = await cmsApi.getBySlug(route.params.slug);
        page.value = data.data ?? data.page ?? data;
        if (page.value?.title) {
            document.title = `${page.value.title} | Onam Dare Challenge`;
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'Page not found';
    } finally {
        loading.value = false;
        uiStore.setLoading(false);
    }
});
</script>

<template>
    <GlassLayout>
        <div v-if="loading" class="text-center text-white py-5">
            <div class="spinner-border text-light" role="status"></div>
        </div>

        <div v-else-if="error" class="glass-card p-4 text-center">
            <p class="text-danger mb-3">{{ error }}</p>
            <router-link to="/" class="btn btn-primary-glass">Go Home</router-link>
        </div>

        <article v-else class="glass-card cms-content p-4">
            <h1 class="cms-title">{{ page.title }}</h1>
            <div class="cms-body" v-html="page.content"></div>
            <router-link to="/" class="btn btn-glass mt-4">← Back</router-link>
        </article>
    </GlassLayout>
</template>

<style scoped>
.cms-title {
    color: #fff;
    font-weight: 800;
    font-size: 1.75rem;
    margin-bottom: 1rem;
}

.cms-body :deep(p) {
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.65;
    margin-bottom: 1rem;
}

.cms-body :deep(h2),
.cms-body :deep(h3) {
    color: #fff;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.cms-body :deep(a) {
    color: var(--accent, #818cf8);
}
</style>
