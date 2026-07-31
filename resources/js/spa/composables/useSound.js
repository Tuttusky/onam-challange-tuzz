import { Howl } from 'howler';
import { useUiStore } from '@/stores/ui';
import { useCampaignStore } from '@/stores/campaign';

const cache = new Map();

export function useSound() {
    const uiStore = useUiStore();
    const campaignStore = useCampaignStore();

    function getHowl(key) {
        const src = campaignStore.theme?.sound_pack?.[key];
        if (!src) {
            return null;
        }

        if (!cache.has(key)) {
            cache.set(
                key,
                new Howl({
                    src: [src],
                    volume: 0.6,
                    preload: true,
                    onloaderror: () => cache.delete(key),
                })
            );
        }

        return cache.get(key);
    }

    function play(key) {
        if (!uiStore.soundsEnabled) {
            return false;
        }

        const sound = getHowl(key);
        if (!sound) {
            return false;
        }

        try {
            const id = sound.play();
            return id !== undefined && id !== null;
        } catch {
            return false;
        }
    }

    function playToneSequence(notes, type = 'sine') {
        if (!uiStore.soundsEnabled) {
            return;
        }

        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return;
            }

            const ctx = new AudioCtx();
            const master = ctx.createGain();
            master.gain.value = 0.18;
            master.connect(ctx.destination);

            notes.forEach(({ freq, at = 0, dur = 150 }) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = type;
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.001, ctx.currentTime + at / 1000);
                gain.gain.exponentialRampToValueAtTime(0.35, ctx.currentTime + at / 1000 + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + at / 1000 + dur / 1000);

                osc.connect(gain);
                gain.connect(master);
                osc.start(ctx.currentTime + at / 1000);
                osc.stop(ctx.currentTime + at / 1000 + dur / 1000 + 0.05);
            });

            const totalMs = Math.max(...notes.map((n) => (n.at ?? 0) + (n.dur ?? 150))) + 80;
            setTimeout(() => ctx.close(), totalMs);
        } catch {
            // Web Audio unavailable — silent fallback
        }
    }

    /** Bright ascending chime — "TRUE" / correct */
    function playTrueSound() {
        playToneSequence([
            { freq: 523.25, at: 0, dur: 90 },
            { freq: 659.25, at: 80, dur: 110 },
            { freq: 783.99, at: 160, dur: 200 },
        ]);
    }

    /** Low descending buzz — "WRONG" */
    function playWrongSound() {
        playToneSequence(
            [
                { freq: 220, at: 0, dur: 140 },
                { freq: 147, at: 110, dur: 280 },
            ],
            'square'
        );
    }

    function tap() {
        play('tap');
    }

    function correct() {
        playTrueSound();
        play('correct');
    }

    function trueSound() {
        correct();
    }

    function wrong() {
        playWrongSound();
        play('wrong');
    }

    function result() {
        play('result');
    }

    return {
        play,
        tap,
        correct,
        true: trueSound,
        wrong,
        result,
    };
}
