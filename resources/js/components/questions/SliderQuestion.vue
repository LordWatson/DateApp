<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue: string | null;
    minimumValue?: number | null;
    maximumValue?: number | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const min = computed(() => props.minimumValue ?? 1);
const max = computed(() => props.maximumValue ?? 10);
const current = computed(() => Number(props.modelValue ?? min.value));

const percentage = computed(() =>
    ((current.value - min.value) / (max.value - min.value)) * 100,
);

function onInput(event: Event): void {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div class="space-y-6 px-2">
        <div class="flex items-center justify-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-primary/10 text-4xl font-semibold text-primary">
                {{ current }}
            </div>
        </div>

        <div class="space-y-2">
            <input
                type="range"
                :min="min"
                :max="max"
                :value="current"
                class="slider-input w-full cursor-pointer appearance-none rounded-full bg-muted focus:outline-none"
                :aria-valuemin="min"
                :aria-valuemax="max"
                :aria-valuenow="current"
                @input="onInput"
            />
            <div class="flex justify-between text-xs text-muted-foreground">
                <span>{{ min }}</span>
                <span>{{ max }}</span>
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
