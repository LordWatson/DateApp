<script setup lang="ts">
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
        return 'Perfect Match ✨';
    }

    if (score >= 75) {
        return 'Great Chemistry 💕';
    }

    if (score >= 60) {
        return 'Good Connection 💫';
    }

    if (score >= 40) {
        return 'Growing Together 🌱';
    }

    return 'Exploring Together 🗺️';
});
</script>

<template>
    <div class="space-y-5 card-premium p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-foreground">Compatibility</h3>
            <span class="text-2xl" aria-hidden="true">💞</span>
        </div>

        <div v-if="score !== null" class="space-y-3">
            <div class="flex items-end gap-2">
                <span class="gradient-text text-5xl font-semibold"
                    >{{ score }}%</span
                >
            </div>
            <p class="text-sm font-medium text-muted-foreground">
                {{ scoreLabel }}
            </p>
            <ProgressBar :value="score" :max="100" />
        </div>

        <div v-else class="flex flex-col items-center gap-3 py-4 text-center">
            <span class="text-4xl" aria-hidden="true">🔮</span>
            <p class="text-sm text-muted-foreground">
                Complete a questionnaire together with {{ partnerName }} to
                reveal your compatibility score.
            </p>
        </div>

        <div v-if="matches.length > 0" class="space-y-2">
            <p
                class="text-xs font-semibold tracking-wide text-success uppercase"
            >
                Shared Preferences
            </p>
            <ul class="space-y-1">
                <li
                    v-for="match in matches"
                    :key="match"
                    class="flex items-center gap-2 text-sm text-foreground"
                >
                    <span class="text-success">✓</span>
                    {{ match }}
                </li>
            </ul>
        </div>

        <div v-if="differences.length > 0" class="space-y-2">
            <p
                class="text-xs font-semibold tracking-wide text-warning uppercase"
            >
                Differences
            </p>
            <ul class="space-y-1">
                <li
                    v-for="diff in differences"
                    :key="diff"
                    class="flex items-center gap-2 text-sm text-foreground"
                >
                    <span class="text-warning">◦</span>
                    {{ diff }}
                </li>
            </ul>
        </div>
    </div>
</template>
