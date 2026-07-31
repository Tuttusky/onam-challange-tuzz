<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import gsap from 'gsap';
import { useAnimation } from '@/composables/useAnimation';
import { useSound } from '@/composables/useSound';

const props = defineProps({
    matchPercent: {
        type: Number,
        default: 0,
    },
    badge: {
        type: Object,
        default: null,
    },
    message: {
        type: String,
        default: '',
    },
    winnerName: {
        type: String,
        default: null,
    },
    showScore: {
        type: Boolean,
        default: true,
    },
    autoDismissMs: {
        type: Number,
        default: 3200,
    },
});

const emit = defineEmits(['dismissed']);

const visible = ref(true);
const canvasRef = ref(null);
const cardRef = ref(null);
const percentRef = ref(null);
const counter = { value: 0 };

let animationFrame = null;
let dismissTimer = null;
let counterTween = null;
let particles = [];

const { fadeIn, fadeOut, pulse } = useAnimation();
const { result: playResultSound } = useSound();

function animateCounter() {
    if (!percentRef.value || !props.showScore) return;

    counterTween?.kill();
    counter.value = 0;

    counterTween = gsap.to(counter, {
        value: props.matchPercent,
        duration: 1.8,
        ease: 'power2.out',
        onUpdate: () => {
            if (percentRef.value) {
                percentRef.value.textContent = `${Math.round(counter.value)}%`;
            }
        },
    });
}

function stopConfetti() {
    if (animationFrame) {
        cancelAnimationFrame(animationFrame);
        animationFrame = null;
    }

    const canvas = canvasRef.value;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (ctx) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    canvas.style.opacity = '0';
}

function dismissCelebration() {
    if (!visible.value) return;

    const finish = () => {
        stopConfetti();
        visible.value = false;
        emit('dismissed');
    };

    if (cardRef.value) {
        fadeOut(cardRef.value, { y: -16, duration: 0.4 })?.eventCallback('onComplete', finish);
        return;
    }

    finish();
}

function scheduleDismiss() {
    if (dismissTimer) {
        window.clearTimeout(dismissTimer);
    }

    dismissTimer = window.setTimeout(dismissCelebration, props.autoDismissMs);
}

function startConfetti() {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    canvas.style.opacity = '1';

    const colors = ['#6366f1', '#818cf8', '#64748b', '#fff', '#a5b4fc'];

    particles = Array.from({ length: 120 }, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height - canvas.height,
        size: Math.random() * 8 + 4,
        color: colors[Math.floor(Math.random() * colors.length)],
        speedY: Math.random() * 3 + 2,
        speedX: Math.random() * 2 - 1,
        rotation: Math.random() * 360,
        spin: Math.random() * 6 - 3,
    }));

    let frame = 0;
    const maxFrames = 180;

    const animate = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        particles.forEach((p) => {
            p.y += p.speedY;
            p.x += p.speedX;
            p.rotation += p.spin;

            if (p.y > canvas.height) {
                p.y = -10;
                p.x = Math.random() * canvas.width;
            }

            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate((p.rotation * Math.PI) / 180);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
            ctx.restore();
        });

        frame += 1;

        if (frame < maxFrames) {
            animationFrame = requestAnimationFrame(animate);
            return;
        }

        stopConfetti();
    };

    animate();
}

onMounted(() => {
    if (cardRef.value) {
        fadeIn(cardRef.value);
        pulse(cardRef.value);
    }

    playResultSound();
    startConfetti();

    if (props.showScore) {
        animateCounter();
    }

    scheduleDismiss();
});

watch(
    () => props.matchPercent,
    () => animateCounter()
);

onBeforeUnmount(() => {
    counterTween?.kill();
    stopConfetti();

    if (dismissTimer) {
        window.clearTimeout(dismissTimer);
        dismissTimer = null;
    }
});
</script>

<template>
    <div v-if="visible" class="result-celebration">
        <canvas ref="canvasRef" class="result-celebration__canvas" aria-hidden="true"></canvas>

        <div ref="cardRef" class="result-celebration__card glass-card text-center">
            <template v-if="showScore">
                <div ref="percentRef" class="result-celebration__percent">0%</div>
                <p class="result-celebration__label">Match Score</p>
            </template>

            <div v-if="badge" class="result-celebration__badge">
                <img v-if="badge.image" :src="badge.image" :alt="badge.name" />
                <span v-else class="result-celebration__badge-emoji">🏅</span>
                <h3>{{ badge.name }}</h3>
            </div>

            <p v-if="winnerName" class="result-celebration__winner">
                🏆 Winner: <strong>{{ winnerName }}</strong>
            </p>

            <p v-if="message" class="result-celebration__message">{{ message }}</p>
        </div>
    </div>
</template>

<style scoped>
.result-celebration {
    position: relative;
    margin-bottom: 1rem;
}

.result-celebration__canvas {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 50;
    opacity: 1;
    transition: opacity 0.35s ease;
}

.result-celebration__card {
    position: relative;
    z-index: 51;
    padding: 2rem 1.5rem;
}

.result-celebration__percent {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1;
    background: linear-gradient(135deg, var(--primary), var(--accent, #818cf8));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.result-celebration__label {
    color: rgba(255, 255, 255, 0.75);
    margin-bottom: 1rem;
}

.result-celebration__badge img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-bottom: 0.5rem;
}

.result-celebration__badge-emoji {
    font-size: 3rem;
    display: block;
    margin-bottom: 0.5rem;
}

.result-celebration__badge h3 {
    color: #fff;
    font-size: 1.25rem;
    font-weight: 700;
}

.result-celebration__winner {
    color: #fff;
    margin-top: 1rem;
}

.result-celebration__message {
    color: rgba(255, 255, 255, 0.85);
    font-size: 1.05rem;
    margin-top: 1rem;
    line-height: 1.5;
}
</style>
