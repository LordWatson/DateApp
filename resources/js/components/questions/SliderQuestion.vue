<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string | null;
    minimumValue?: number | null;
    maximumValue?: number | null;
    stepValue?: number | null;
    unit?: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const min = computed(() => props.minimumValue ?? 1);
const max = computed(() => props.maximumValue ?? 10);
const step = computed(() => props.stepValue ?? 1);
const current = computed(() => Number(props.modelValue ?? min.value));

const percentage = computed(() => {
    if (max.value === min.value) {
        return 0;
    }

    return ((current.value - min.value) / (max.value - min.value)) * 100;
});

function formatMinutes(value: number): string {
    if (value <= 0) {
        return 'Right here';
    }

    const hours = Math.floor(value / 60);
    const mins = value % 60;

    if (hours === 0) {
        return `${mins} min`;
    }

    if (mins === 0) {
        return hours === 1 ? '1 hour' : `${hours} hours`;
    }

    return `${hours}h ${mins}m`;
}

function formatValue(value: number): string {
    if (props.unit === 'minutes') {
        return formatMinutes(value);
    }

    if (props.unit) {
        return `${value} ${props.unit}`;
    }

    return String(value);
}

const displayCurrent = computed(() => formatValue(current.value));
const displayMin = computed(() => formatValue(min.value));
const displayMax = computed(() => formatValue(max.value));

function onInput(event: Event): void {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div class="space-y-8 card-premium px-5 py-8">
        <p
            class="text-center font-display text-5xl leading-none font-medium text-foreground tabular-nums"
        >
            {{ displayCurrent }}
        </p>

        <div class="space-y-3">
            <input
                type="range"
                :min="min"
                :max="max"
                :step="step"
                :value="current"
                class="slider-input block w-full cursor-pointer appearance-none rounded-full focus:outline-none"
                :aria-valuemin="min"
                :aria-valuemax="max"
                :aria-valuenow="current"
                @input="onInput"
            />
            <div
                class="flex justify-between text-xs text-muted-foreground tabular-nums"
            >
                <span>{{ displayMin }}</span>
                <span>{{ displayMax }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slider-input {
    height: 6px;
    background-color: var(--muted);
    background-image: var(--gradient-brand);
    background-repeat: no-repeat;
    background-size: v-bind('`${percentage}%`') 100%;
}

.slider-input::-webkit-slider-thumb {
    appearance: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--card);
    border: 2px solid var(--primary);
    cursor: pointer;
    box-shadow: 0 2px 6px var(--shadow-color);
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
}

.slider-input::-webkit-slider-thumb:active {
    transform: scale(1.1);
}

.slider-input:focus-visible::-webkit-slider-thumb {
    box-shadow: 0 0 0 5px color-mix(in srgb, var(--primary) 18%, transparent);
}

.slider-input::-moz-range-thumb {
    box-sizing: border-box;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--card);
    border: 2px solid var(--primary);
    cursor: pointer;
    box-shadow: 0 2px 6px var(--shadow-color);
}

.slider-input:focus-visible::-moz-range-thumb {
    box-shadow: 0 0 0 5px color-mix(in srgb, var(--primary) 18%, transparent);
}
</style>
