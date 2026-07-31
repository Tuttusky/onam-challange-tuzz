import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import * as pottuApi from '@/api/pottu';
import { resolvePottuImageUrl } from '@/composables/resolvePottuImageUrl';

function normalizeImages(images = []) {
    return images.map((image) => ({
        ...image,
        url: resolvePottuImageUrl(image),
    }));
}

export const usePottuStore = defineStore('pottu', () => {
    const images = ref([]);
    const styles = ref([]);
    const settings = ref({});
    const selectedImageId = ref(null);
    const selectedStyleId = ref(null);
    const placement = ref(null);
    const role = ref('creator');
    const challengeMeta = ref(null);
    const creatorTarget = ref(null);
    const referenceSize = ref(null);
    const result = ref(null);
    const canCreateNext = ref(false);
    const parentLinkId = ref(null);

    const selectedImage = computed(() =>
        images.value.find((img) => img.id === selectedImageId.value) ?? null
    );

    const selectedStyle = computed(() =>
        styles.value.find((style) => style.id === selectedStyleId.value) ?? styles.value[0] ?? null
    );

    function hydrateFromBootstrap(payload) {
        images.value = normalizeImages(payload.images ?? []);
        styles.value = payload.styles ?? [];
        settings.value = payload.settings ?? {};
        role.value = payload.role ?? 'creator';
        challengeMeta.value = payload.challenge ?? null;
        creatorTarget.value = payload.creator_target ?? null;
        referenceSize.value = payload.reference_size ?? null;

        if (payload.selected_image_id) {
            selectedImageId.value = payload.selected_image_id;
        } else if (images.value.length === 1) {
            selectedImageId.value = images.value[0].id;
        }

        if (styles.value.length && !selectedStyleId.value) {
            selectedStyleId.value = styles.value[0].id;
        }
    }

    async function loadConfig(slug) {
        const { data } = await pottuApi.getConfig(slug);
        const payload = data.data ?? data;
        images.value = normalizeImages(payload.images ?? []);
        styles.value = payload.styles ?? [];
        settings.value = payload.settings ?? {};
    }

    function setPlacement(value) {
        placement.value = value;
    }

    function setResult(value) {
        result.value = value;
        canCreateNext.value = Boolean(value?.can_create_next_challenge ?? value?.won);
    }

    function setParentLinkId(id) {
        parentLinkId.value = id;
    }

    function reset() {
        images.value = [];
        styles.value = [];
        settings.value = {};
        selectedImageId.value = null;
        selectedStyleId.value = null;
        placement.value = null;
        role.value = 'creator';
        challengeMeta.value = null;
        creatorTarget.value = null;
        referenceSize.value = null;
        result.value = null;
        canCreateNext.value = false;
        parentLinkId.value = null;
    }

    return {
        images,
        styles,
        settings,
        selectedImageId,
        selectedStyleId,
        placement,
        role,
        challengeMeta,
        creatorTarget,
        referenceSize,
        result,
        canCreateNext,
        parentLinkId,
        selectedImage,
        selectedStyle,
        hydrateFromBootstrap,
        loadConfig,
        setPlacement,
        setResult,
        setParentLinkId,
        reset,
    };
});
