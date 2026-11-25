<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Star } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { history } from '@/routes/date-night';

defineOptions({ layout: MobileLayout });

interface PlanSummary {
    id: number;
    theme: string;
    theme_emoji: string | null;
    compatibility_score: number | null;
    is_favourite: boolean;
    created_at: string;
    questionnaire_title: string | null;
}

interface Filters {
    search: string;
    theme: string;
    compatibility: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPlans {
    data: PlanSummary[];
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
    last_page: number;
}

const props = defineProps<{
    plans: PaginatedPlans;
    themes: string[];
    filters: Filters;
}>();

const search = ref(props.filters.search);
const theme = ref(props.filters.theme);
const compatibility = ref(props.filters.compatibility);

const hasFilters = computed(
    () =>
        search.value !== '' || theme.value !== '' || compatibility.value !== '',
);

const compatibilityOptions = [
    { value: '', label: 'All' },
    { value: 'high', label: 'High' },
    { value: 'medium', label: 'Medium' },
    { value: 'low', label: 'Low' },
];

function applyFilters(): void {
    // Changing a filter always goes back to the first page.
    router.get(
        history().url,
        {
            search: search.value || undefined,
            theme: theme.value || undefined,
            compatibility: compatibility.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString([], {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function scoreColour(score: number): string {
    if (score >= 75) {
        return 'text-success';
    }

    if (score >= 50) {
        return 'text-warning';
    }

    return 'text-destructive';
}
</script>

<template>
    <Head title="Date night history" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Date nights"
            title="History"
            description="All your date night plans"
        />

        <!-- Filters -->
        <div class="space-y-3">
            <input
                v-model="search"
                type="text"
                placeholder="Search plans..."
                aria-label="Search plans"
                class="input-premium"
                @input="applyFilters"
            />

            <select
                v-if="themes.length > 1 || theme !== ''"
                v-model="theme"
                aria-label="Filter by theme"
                class="input-premium"
                @change="applyFilters"
            >
                <option value="">All themes</option>
                <option v-for="name in themes" :key="name" :value="name">
                    {{ name }}
                </option>
            </select>

            <div
                class="flex gap-2 overflow-x-auto pb-1"
                role="group"
                aria-label="Filter by compatibility"
            >
                <button
                    v-for="option in compatibilityOptions"
                    :key="option.value"
                    type="button"
                    class="chip"
                    :aria-pressed="compatibility === option.value"
                    @click="
                        compatibility = option.value;
                        applyFilters();
                    "
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <!-- Plans -->
        <div class="card-premium p-2">
            <template v-if="plans.data.length > 0">
                <button
                    v-for="plan in plans.data"
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
                        <p
                            class="flex items-center gap-1.5 font-medium text-foreground"
                        >
                            <span class="truncate">{{ plan.theme }}</span>
                            <Star
                                v-if="plan.is_favourite"
                                class="size-3.5 shrink-0 fill-current text-gold"
                                aria-label="Favourite"
                            />
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ formatDate(plan.created_at) }}
                            <template v-if="plan.questionnaire_title">
                                · {{ plan.questionnaire_title }}
                            </template>
                        </p>
                    </div>
                    <span
                        v-if="plan.compatibility_score !== null"
                        :class="[
                            'font-display text-xl font-medium tabular-nums',
                            scoreColour(plan.compatibility_score),
                        ]"
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
                v-else-if="hasFilters"
                emoji="🔎"
                title="No plans match"
                description="Try a different search or clear the filters."
            />
            <EmptyState
                v-else
                emoji="📖"
                title="No plans yet"
                description="Complete a questionnaire with your partner to generate your first date night plan."
            />
        </div>

        <!-- Pagination -->
        <nav
            v-if="plans.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1.5"
            aria-label="Pagination"
        >
            <Link
                v-for="link in plans.links"
                :key="link.label"
                :href="link.url ?? '#'"
                preserve-scroll
                class="chip min-w-10 justify-center tabular-nums"
                :class="!link.url ? 'pointer-events-none opacity-40' : ''"
                :data-active="link.active"
                :aria-current="link.active ? 'page' : undefined"
            >
                <span v-html="link.label" />
            </Link>
        </nav>

        <p
            v-if="plans.total > 0"
            class="text-center text-xs text-muted-foreground tabular-nums"
        >
            Showing {{ plans.from }}–{{ plans.to }} of {{ plans.total }}
        </p>

        <PrimaryButton full-width @click="router.visit('/questionnaires')">
            Start a questionnaire
        </PrimaryButton>
    </div>
</template>
