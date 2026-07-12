<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface CompatibilityEntry {
    question: string;
    emoji: string | null;
    user_answer: string;
    partner_answer: string;
    score?: number;
    type: string;
}

interface CompatibilityBreakdown {
    total_questions: number;
    matched_count: number;
    different_count: number;
}

interface CompatibilityData {
    percentage: number;
    matched: CompatibilityEntry[];
    different: CompatibilityEntry[];
    breakdown: CompatibilityBreakdown;
}

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
}

interface Props {
    questionnaire: QuestionnaireData;
    partner_name: string | null;
    partner_completed: boolean;
    compatibility: CompatibilityData | null;
}

const props = defineProps<Props>();

const circumference = 2 * Math.PI * 45;
const dashOffset = computed(() => {
    if (!props.compatibility) {
        return circumference;
    }

    return circumference - (props.compatibility.percentage / 100) * circumference;
});

const scoreColour = computed(() => {
    if (!props.compatibility) {
        return '#9333EA';
    }

    if (props.compatibility.percentage >= 80) {
        return '#10B981';
    }

    if (props.compatibility.percentage >= 60) {
        return '#F59E0B';
    }

    return '#EC4899';
});
</script>

<template>
    <Head title="Compatibility" />

    <div class="relative min-h-screen overflow-hidden">
        <FloatingHearts />

        <div class="relative z-10 space-y-6 px-4 py-6 pb-24">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-foreground">Compatibility 💕</h1>
                <p class="text-sm text-muted-foreground">
                    {{ partner_name ? `You & ${partner_name}` : 'You & your partner' }}
                </p>
            </div>

            <!-- Waiting state -->
            <div v-if="!partner_completed" class="card-premium p-8 text-center space-y-4">
                <div class="text-6xl animate-pulse">⏳</div>
                <h2 class="text-xl font-semibold text-foreground">Waiting for your partner ❤️</h2>
                <p class="text-sm text-muted-foreground">
                    Once {{ partner_name ?? 'your partner' }} completes the questionnaire,
                    your compatibility score will appear here.
                </p>
                <SecondaryButton full-width @click="router.visit('/dashboard')">
                    Return Home
                </SecondaryButton>
            </div>

            <!-- Compatibility results -->
            <template v-else-if="compatibility">
                <!-- Score circle -->
                <div class="card-premium p-6 flex flex-col items-center space-y-4">
                    <svg width="120" height="120" viewBox="0 0 100 100" class="-rotate-90">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="8" />
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            fill="none"
                            :stroke="scoreColour"
                            stroke-width="8"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="dashOffset"
                            class="transition-all duration-1000 ease-out"
                        />
                    </svg>
                    <div class="absolute text-center" style="margin-top: -80px;">
                        <p class="text-3xl font-semibold" :style="{ color: scoreColour }">
                            {{ compatibility.percentage }}%
                        </p>
                    </div>
                    <div class="text-center space-y-1">
                        <p class="text-lg font-semibold text-foreground">Overall Match</p>
                        <p class="text-sm text-muted-foreground">
                            {{ compatibility.breakdown.matched_count }} matched ·
                            {{ compatibility.breakdown.different_count }} differed
                        </p>
                    </div>
                </div>

                <!-- Matched answers -->
                <div v-if="compatibility.matched.length > 0" class="space-y-3">
                    <h2 class="text-base font-semibold text-foreground">✅ Things you both matched on</h2>
                    <div
                        v-for="(entry, i) in compatibility.matched"
                        :key="i"
                        class="card-premium p-4 space-y-2"
                    >
                        <div class="flex items-center gap-2">
                            <span v-if="entry.emoji" class="text-xl">{{ entry.emoji }}</span>
                            <p class="text-sm font-semibold text-foreground">{{ entry.question }}</p>
                        </div>
                        <div v-if="entry.type !== 'text'" class="flex items-center gap-2 text-sm text-success font-medium">
                            <span>{{ entry.user_answer }}</span>
                        </div>
                        <div v-else class="space-y-1 text-xs text-muted-foreground">
                            <p><span class="font-medium">You:</span> {{ entry.user_answer }}</p>
                            <p><span class="font-medium">{{ partner_name ?? 'Partner' }}:</span> {{ entry.partner_answer }}</p>
                        </div>
                    </div>
                </div>

                <!-- Different answers -->
                <div v-if="compatibility.different.length > 0" class="space-y-3">
                    <h2 class="text-base font-semibold text-foreground">💬 Things that differed</h2>
                    <div
                        v-for="(entry, i) in compatibility.different"
                        :key="i"
                        class="card-premium p-4 space-y-2"
                    >
                        <div class="flex items-center gap-2">
                            <span v-if="entry.emoji" class="text-xl">{{ entry.emoji }}</span>
                            <p class="text-sm font-semibold text-foreground">{{ entry.question }}</p>
                        </div>
                        <div class="space-y-1 text-xs">
                            <p class="text-muted-foreground"><span class="font-medium text-foreground">You:</span> {{ entry.user_answer }}</p>
                            <p class="text-muted-foreground"><span class="font-medium text-foreground">{{ partner_name ?? 'Partner' }}:</span> {{ entry.partner_answer }}</p>
                        </div>
                    </div>
                </div>

                <PrimaryButton full-width @click="router.visit('/dashboard')">
                    Return Home ❤️
                </PrimaryButton>
            </template>
        </div>
    </div>
</template>
