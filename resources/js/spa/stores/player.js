import { defineStore } from 'pinia';
import { ref } from 'vue';
import { deviceStorage } from '@/utils/deviceStorage';

const UUID_KEY = 'player_uuid';

export const usePlayerStore = defineStore('player', () => {
    const uuid = ref(localStorage.getItem(UUID_KEY) || null);
    const name = ref(deviceStorage.getPlayerName());

    function setPlayer(player) {
        if (player?.uuid) {
            uuid.value = player.uuid;
            localStorage.setItem(UUID_KEY, player.uuid);
        }
        if (player?.name) {
            name.value = player.name;
            deviceStorage.savePlayerName(player.name);
            deviceStorage.saveCreatorName(player.name);
        }
    }

    function setName(value) {
        const trimmed = value.trim();
        name.value = trimmed;
        deviceStorage.savePlayerName(trimmed);
        deviceStorage.saveCreatorName(trimmed);
    }

    function clear() {
        uuid.value = null;
        name.value = '';
        localStorage.removeItem(UUID_KEY);
        deviceStorage.savePlayerName('');
    }

    return {
        uuid,
        name,
        setPlayer,
        setName,
        clear,
    };
});
