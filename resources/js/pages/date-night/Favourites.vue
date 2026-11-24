<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
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
    return new Date(iso).toLocaleDateString([], {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Favourite plans" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Date nights"
            title="Favourites"
            description="Your saved date night plans"
        />

        <div class="card-premium p-2">
            <template v-if="plans.length > 0">
                <button
                    v-for="plan in plans"
                    :key="plan.id"
                    type="button"
                    class="list-row w-full text-left"
                    @click="router.visit(`/date-night/${plan.id}`)"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                        >{{ plan.theme_emoji ?? '❤️' }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-foreground">
                            {{ plan.theme }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ formatDate(plan.created_at) }}
                            <template v-if="plan.questionnaire_title">
                                · {{ plan.questionnaire_title }}
                            </template>
                        </p>
                    </div>
                    <span
                        class="font-display text-xl font-medium text-foreground tabular-nums"
                    >
                        {{ plan.compatibility_score }}%
                    </span>
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                </button>
            </template>

            <EmptyState
                v-else
                emoji="💛"
                title="No favourites yet"
                description="View a date night plan and save it to your favourites."
            />
        </div>

        <PrimaryButton full-width @click="router.visit('/date-night/history')">
            View all plans
        </PrimaryButton>
    </div>
</template>
