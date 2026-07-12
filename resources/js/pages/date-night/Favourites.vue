<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface PlanSummary {
    id: number;
    theme: string;
    theme_emoji: string | null;
    compatibility_score: number;
    is_favourite: boolean;
    created_at: string;
    questionnaire_title: string | null;
}

defineProps<{ plans: PlanSummary[] }>();

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Favourite Plans 💛" />

    <div class="min-h-screen px-4 py-6 pb-28">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">💛 Favourites</h1>
            <p class="mt-1 text-sm text-muted-foreground">Your saved Date Night plans</p>
        </div>

        <div v-if="plans.length > 0" class="space-y-3">
            <div
                v-for="plan in plans"
                :key="plan.id"
                class="card-premium card-hover cursor-pointer p-5"
                @click="router.visit(`/date-night/${plan.id}`)"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ plan.theme_emoji ?? '❤️' }}</span>
                        <div>
                            <p class="font-semibold text-foreground">{{ plan.theme }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatDate(plan.created_at) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="text-lg font-bold text-pink-500">{{ plan.compatibility_score }}%</span>
                        <span class="text-sm">💛</span>
                    </div>
                </div>
                <p v-if="plan.questionnaire_title" class="mt-2 text-xs text-muted-foreground">
                    {{ plan.questionnaire_title }}
                </p>
            </div>
        </div>

        <EmptyState
            v-else
            emoji="💛"
            title="No favourites yet"
            description="View a Date Night plan and save it to your favourites."
        />

        <div class="mt-6 space-y-3">
            <PrimaryButton full-width @click="router.visit('/date-night/history')">
                View All Plans
            </PrimaryButton>
        </div>
    </div>
</template>
