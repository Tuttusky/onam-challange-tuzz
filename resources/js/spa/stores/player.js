import { defineStore } from 'pinia';
import { ref } from 'vue';

const UUID_KEY = 'player_uuid';
const NAME_KEY = 'player_name';

export const usePlayerStore = defineStore('player', () => {
    const uuid = ref(localStorage.getItem(UUID_KEY) || null);
    const name = ref(localStorage.getItem(NAME_KEY) || '');

    function setPlayer(player) {
        if (player?.uuid) {
            uuid.value = player.uuid;
            localStorage.setItem(UUID_KEY, player.uuid);
        }
        if (player?.name) {
            name.value = player.name;
            localStorage.setItem(NAME_KEY, player.name);
        }
    }

    function setName(value) {
        name.value = value.trim();
        localStorage.setItem(NAME_KEY, name.value);
    }

    function clear() {
        uuid.value = null;
        name.value = '';
        localStorage.removeItem(UUID_KEY);
        localStorage.removeItem(NAME_KEY);
    }

    return {
        uuid,
        name,
        setPlayer,
        setName,
        clear,
    };
});
