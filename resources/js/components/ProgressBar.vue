<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    value: number;
    max?: number;
    showLabel?: boolean;
    label?: string;
}

const { value, max = 100, showLabel = false, label } = defineProps<Props>();

const percentage = computed(() =>
    Math.min(100, Math.max(0, (value / max) * 100)),
);
</script>

<template>
    <div class="w-full space-y-1.5">
        <div
            v-if="showLabel || label"
            class="flex items-center justify-between text-sm"
        >
            <span class="font-medium text-foreground">{{ label }}</span>
            <span class="text-muted-foreground"
                >{{ Math.round(percentage) }}%</span
            >
        </div>
        <div
            class="h-2.5 w-full overflow-hidden rounded-full bg-muted"
            role="progressbar"
            :aria-valuenow="value"
            :aria-valuemin="0"
            :aria-valuemax="max"
        >
            <div
                class="animate-progress-fill h-full rounded-full transition-all duration-700 ease-out gradient-primary"
                :style="{ width: `${percentage}%` }"
            />
        </div>
    </div>
</template>
