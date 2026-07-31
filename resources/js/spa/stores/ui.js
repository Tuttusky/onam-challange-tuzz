import { defineStore } from 'pinia';
import { ref } from 'vue';

const SOUND_KEY = 'sounds_enabled';
const DARK_KEY = 'dark_mode';

export const useUiStore = defineStore('ui', () => {
    const loading = ref(false);
    const soundsEnabled = ref(localStorage.getItem(SOUND_KEY) !== 'false');
    const darkMode = ref(localStorage.getItem(DARK_KEY) === 'true');
    const toast = ref(null);

    function setLoading(value) {
        loading.value = value;
    }

    function toggleSounds() {
        soundsEnabled.value = !soundsEnabled.value;
        localStorage.setItem(SOUND_KEY, String(soundsEnabled.value));
    }

    function toggleDarkMode() {
        darkMode.value = !darkMode.value;
        localStorage.setItem(DARK_KEY, String(darkMode.value));
    }

    function showToast(message, type = 'info') {
        toast.value = { message, type };
        setTimeout(() => {
            toast.value = null;
        }, 3500);
    }

    return {
        loading,
        soundsEnabled,
        darkMode,
        toast,
        setLoading,
        toggleSounds,
        toggleDarkMode,
        showToast,
    };
});
