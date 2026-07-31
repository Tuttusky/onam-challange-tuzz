<script setup>
import { computed } from 'vue';
import { usePottuFlowI18n } from '@/composables/usePottuFlowI18n';

const props = defineProps({
    currentStep: {
        type: Number,
        required: true,
        validator: (value) => value >= 1 && value <= 5,
    },
    totalSteps: {
        type: Number,
        default: 5,
    },
});

const { t, stepLabels } = usePottuFlowI18n();

const stepLabel = computed(() =>
    t('step_of', { current: props.currentStep, total: props.totalSteps })
);
</script>

<template>
    <div class="pottu-flow-stepper" :aria-label="stepLabel">
        <p class="pottu-flow-stepper__label">{{ stepLabel }}</p>
        <div class="pottu-flow-stepper__track">
            <template v-for="(label, index) in stepLabels" :key="index">
                <div
                    class="pottu-flow-stepper__item"
                    :class="{
                        'pottu-flow-stepper__item--active': currentStep === index + 1,
                        'pottu-flow-stepper__item--done': currentStep > index + 1,
                    }"
                >
                    <span class="pottu-flow-stepper__dot">
                        <template v-if="currentStep > index + 1">✓</template>
                        <template v-else>{{ index + 1 }}</template>
                    </span>
                    <span class="pottu-flow-stepper__text">{{ label }}</span>
                </div>
                <div
                    v-if="index < stepLabels.length - 1"
                    class="pottu-flow-stepper__line"
                    :class="{ 'pottu-flow-stepper__line--active': currentStep > index + 1 }"
                />
            </template>
        </div>
    </div>
</template>

<style scoped>
.pottu-flow-stepper {
    text-align: center;
    margin-bottom: 1rem;
}

.pottu-flow-stepper__label {
    margin: 0 0 0.65rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #9ca3af;
}

.pottu-flow-stepper__track {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 0;
    overflow-x: auto;
    padding-bottom: 0.25rem;
    -webkit-overflow-scrolling: touch;
}

.pottu-flow-stepper__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    width: 58px;
    flex-shrink: 0;
}

.pottu-flow-stepper__dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #c7d2fe;
    background: transparent;
    color: #9ca3af;
    font-weight: 700;
    font-size: 0.65rem;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.pottu-flow-stepper__item--active .pottu-flow-stepper__dot {
    border-color: #ea580c;
    background: #ea580c;
    color: #fff;
}

.pottu-flow-stepper__item--done .pottu-flow-stepper__dot {
    border-color: #22c55e;
    background: #22c55e;
    color: #fff;
}

.pottu-flow-stepper__text {
    font-size: 0.58rem;
    font-weight: 700;
    color: #9ca3af;
    line-height: 1.15;
    max-width: 58px;
}

.pottu-flow-stepper__item--active .pottu-flow-stepper__text {
    color: #ea580c;
}

.pottu-flow-stepper__item--done .pottu-flow-stepper__text {
    color: #16a34a;
}

.pottu-flow-stepper__line {
    width: 14px;
    height: 2px;
    background: #c7d2fe;
    margin-top: 10px;
    flex-shrink: 0;
    opacity: 0.45;
}

.pottu-flow-stepper__line--active {
    background: #22c55e;
    opacity: 1;
}

@media (min-width: 400px) {
    .pottu-flow-stepper__item {
        width: 64px;
    }

    .pottu-flow-stepper__text {
        font-size: 0.62rem;
        max-width: 64px;
    }

    .pottu-flow-stepper__line {
        width: 18px;
    }
}
</style>
