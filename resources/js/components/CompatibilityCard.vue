<script setup lang="ts">
import { Check, Minus } from '@lucide/vue';
import { computed } from 'vue';
import ProgressBar from '@/components/ProgressBar.vue';

interface Props {
    score?: number | null;
    partnerName?: string;
    matches?: string[];
    differences?: string[];
}

const {
    score = null,
    partnerName = 'your partner',
    matches = [],
    differences = [],
} = defineProps<Props>();

const scoreLabel = computed(() => {
    if (score === null) {
        return null;
    }

    if (score >= 90) {
        return 'Perfect match';
    }

    if (score >= 75) {
        return 'Great chemistry';
    }

    if (score >= 60) {
        return 'Good connection';
    }

    if (score >= 40) {
        return 'Growing together';
    }

    return 'Exploring together';
});
</script>

<template>
    <div class="space-y-5 card-premium p-6">
        <div v-if="score !== null" class="space-y-3">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <h3 class="eyebrow">Compatibility</h3>
                    <p
                        class="font-display text-5xl leading-none font-medium text-foreground tabular-nums"
                    >
                        {{ score }}%
                    </p>
                </div>
                <span class="icon-tile h-11 w-11 text-xl" aria-hidden="true"
                    >💞</span
                >
            </div>
            <p class="text-sm font-medium text-muted-foreground">
                {{ scoreLabel }}
            </p>
            <ProgressBar :value="score" :max="100" />
        </div>

        <div v-else class="flex flex-col items-center gap-4 py-2 text-center">
            <span class="icon-tile h-14 w-14 text-3xl" aria-hidden="true"
                >🔮</span
            >
            <div class="space-y-1.5">
                <h3 class="text-lg font-semibold text-foreground">
                    Compatibility
                </h3>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Complete a questionnaire together with {{ partnerName }} to
                    reveal your compatibility score.
                </p>
            </div>
        </div>

        <div v-if="matches.length > 0" class="space-y-2">
            <p class="eyebrow">Shared preferences</p>
            <ul class="space-y-1.5">
                <li
                    v-for="match in matches"
                    :key="match"
                    class="flex items-start gap-2 text-sm text-foreground"
                >
                    <Check
                        class="mt-0.5 size-4 shrink-0 text-success"
                        aria-hidden="true"
                    />
                    {{ match }}
                </li>
            </ul>
        </div>

        <div v-if="differences.length > 0" class="space-y-2">
            <p class="eyebrow">Differences</p>
            <ul class="space-y-1.5">
                <li
                    v-for="diff in differences"
                    :key="diff"
                    class="flex items-start gap-2 text-sm text-foreground"
                >
                    <Minus
                        class="mt-0.5 size-4 shrink-0 text-warning"
                        aria-hidden="true"
                    />
                    {{ diff }}
                </li>
            </ul>
        </div>
    </div>
</template>
