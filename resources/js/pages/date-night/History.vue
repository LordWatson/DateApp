<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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

interface Filters {
    search: string;
    theme: string;
    compatibility: string;
}

const props = defineProps<{ plans: PlanSummary[]; filters: Filters }>();

const search = ref(props.filters.search);
const compatibility = ref(props.filters.compatibility);

function applyFilters(): void {
    router.get('/date-night/history', {
        search: search.value,
        compatibility: compatibility.value,
    }, { preserveState: true, replace: true });
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
}

function scoreColour(score: number): string {
    if (score >= 75) {
        return 'text-emerald-600';
    }

    if (score >= 50) {
        return 'text-amber-500';
    }

    return 'text-rose-500';
}
</script>

<template>
    <Head title="Date Night History 📖" />

    <div class="min-h-screen px-4 py-6 pb-28">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">📖 History</h1>
            <p class="mt-1 text-sm text-muted-foreground">All your Date Night plans</p>
        </div>

        <!-- Filters -->
        <div class="mb-6 space-y-3">
            <input
                v-model="search"
                type="text"
                placeholder="Search plans..."
                class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                @input="applyFilters"
            />

            <div class="flex gap-2">
                <button
                    v-for="option in [{ value: '', label: 'All' }, { value: 'high', label: '🟢 High' }, { value: 'medium', label: '🟡 Medium' }, { value: 'low', label: '🔴 Low' }]"
                    :key="option.value"
                    :class="[
                        'flex-1 rounded-2xl px-3 py-2 text-xs font-semibold transition-all',
                        compatibility === option.value
                            ? 'gradient-primary text-white shadow-md'
                            : 'bg-card text-muted-foreground shadow-sm',
                    ]"
                    @click="compatibility = option.value; applyFilters()"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <!-- Plans -->
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
                        <span :class="['text-lg font-bold', scoreColour(plan.compatibility_score)]">
                            {{ plan.compatibility_score }}%
                        </span>
                        <span v-if="plan.is_favourite" class="text-sm">💛</span>
                    </div>
                </div>
                <p v-if="plan.questionnaire_title" class="mt-2 text-xs text-muted-foreground">
                    {{ plan.questionnaire_title }}
                </p>
            </div>
        </div>

        <EmptyState
            v-else
            emoji="📖"
            title="No plans yet"
            description="Complete a questionnaire with your partner to generate your first Date Night plan."
        />

        <div class="mt-6">
            <PrimaryButton full-width @click="router.visit('/questionnaires')">
                Start a Questionnaire
            </PrimaryButton>
        </div>
    </div>
</template>
