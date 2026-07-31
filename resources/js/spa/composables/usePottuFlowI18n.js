import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useLocaleStore } from '@/stores/locale';
import { translate, challengeTitle, challengeMessage } from '@/i18n/pottuFlow';

export function usePottuFlowI18n() {
    const localeStore = useLocaleStore();
    const { locale } = storeToRefs(localeStore);

    function t(key, params = {}) {
        return translate(locale.value, key, params);
    }

    const stepLabels = computed(() => [
        t('step_name'),
        t('step_language'),
        t('step_choose_image'),
        t('step_set_pottu'),
        t('step_invite'),
    ]);

    return {
        locale,
        t,
        stepLabels,
        challengeTitle: (name) => challengeTitle(locale.value, name),
        challengeMessage: (name) => challengeMessage(locale.value, name),
        setLocale: localeStore.setLocale,
    };
}
