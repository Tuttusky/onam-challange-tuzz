import { defineStore } from 'pinia';
import { ref } from 'vue';

const LOCALE_KEY = 'player_locale';
const SUPPORTED = new Set(['en', 'ml']);

export const useLocaleStore = defineStore('locale', () => {
    const stored = localStorage.getItem(LOCALE_KEY);
    const locale = ref(SUPPORTED.has(stored) ? stored : 'en');

    function setLocale(code) {
        if (!SUPPORTED.has(code)) {
            return;
        }

        locale.value = code;
        localStorage.setItem(LOCALE_KEY, code);
    }

    function applyFromQuery(code) {
        if (code && SUPPORTED.has(code)) {
            setLocale(code);
        }
    }

    return {
        locale,
        setLocale,
        applyFromQuery,
    };
});
