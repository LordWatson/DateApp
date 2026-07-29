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
    <div class="space-y-6 px-2">
        <div class="flex items-center justify-center">
            <div class="flex min-h-20 min-w-20 items-center justify-center rounded-3xl bg-primary/10 px-5 py-3 text-center text-2xl font-semibold text-primary">
                {{ displayCurrent }}
            </div>
        </div>

        <div class="space-y-2">
            <input
                type="range"
                :min="min"
                :max="max"
                :step="step"
                :value="current"
                class="slider-input w-full cursor-pointer appearance-none rounded-full bg-muted focus:outline-none"
                :aria-valuemin="min"
                :aria-valuemax="max"
                :aria-valuenow="current"
                @input="onInput"
            />
            <div class="flex justify-between text-xs text-muted-foreground">
                <span>{{ displayMin }}</span>
                <span>{{ displayMax }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slider-input {
    height: 8px;
    background: linear-gradient(
        to right,
        #ec4899 0%,
        #9333ea v-bind('`${percentage}%`'),
        #e5e7eb v-bind('`${percentage}%`'),
        #e5e7eb 100%
    );
}

.slider-input::-webkit-slider-thumb {
    appearance: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ec4899, #9333ea);
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(236, 72, 153, 0.4);
    transition: transform 0.15s ease;
}

.slider-input::-webkit-slider-thumb:active {
    transform: scale(1.2);
}

.slider-input::-moz-range-thumb {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ec4899, #9333ea);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 8px rgba(236, 72, 153, 0.4);
}
</style>
