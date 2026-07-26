<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface Questionnaire {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    emoji: string | null;
    estimated_minutes: number | null;
    question_count: number;
    response_status: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedQuestionnaires {
    data: Questionnaire[];
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
    current_page: number;
    last_page: number;
}

type FilterValue = 'all' | 'couples' | 'solo' | 'intimacy' | 'seasonal';

interface Props {
    questionnaires: PaginatedQuestionnaires;
    filters: { filter: FilterValue };
}

const props = defineProps<Props>();

const filterOptions: ReadonlyArray<{ value: FilterValue; label: string; emoji: string }> = [
    { value: 'all', label: 'All', emoji: '✨' },
    { value: 'couples', label: 'Couples', emoji: '💞' },
    { value: 'solo', label: 'Solo', emoji: '🌙' },
    { value: 'intimacy', label: 'Intimacy', emoji: '🔥' },
    { value: 'seasonal', label: 'Seasonal', emoji: '🍂' },
];

function applyFilter(value: FilterValue): void {
    router.get(
        '/questionnaires',
        { filter: value },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function statusLabel(status: string | null): string {
    if (status === 'completed') {
        return 'Completed ✓';
    }

    if (status === 'in_progress') {
        return 'In Progress…';
    }

    return 'Not started';
}

function statusClass(status: string | null): string {
    if (status === 'completed') {
        return 'text-success';
    }

    if (status === 'in_progress') {
        return 'text-warning';
    }

    return 'text-muted-foreground';
}

function openQuestionnaire(slug: string): void {
    router.visit(`/questionnaires/${slug}`);
}
</script>

<template>
    <Head title="Plan" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-foreground">Plan 📋</h1>
            <p class="text-sm text-muted-foreground">Choose a questionnaire to plan your evening.</p>
        </div>

        <!-- Filter pills -->
        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
            <button
                v-for="option in filterOptions"
                :key="option.value"
                type="button"
                class="flex min-h-[44px] shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-colors"
                :class="
                    props.filters.filter === option.value
                        ? 'bg-primary text-white shadow'
                        : 'card-premium text-foreground'
                "
                :aria-pressed="props.filters.filter === option.value"
                @click="applyFilter(option.value)"
            >
                <span aria-hidden="true">{{ option.emoji }}</span>
                {{ option.label }}
            </button>
        </div>

        <div v-if="questionnaires.data.length === 0" class="card-premium p-8 text-center">
            <p class="text-4xl">📭</p>
            <p class="mt-3 font-semibold text-foreground">No questionnaires found</p>
            <p class="mt-1 text-sm text-muted-foreground">Try a different filter above.</p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="q in questionnaires.data"
                :key="q.id"
                class="card-premium cursor-pointer p-6 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99]"
                @click="openQuestionnaire(q.slug)"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-3xl">
                        {{ q.emoji ?? '💕' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-foreground">{{ q.title }}</h2>
                        <p v-if="q.description" class="mt-0.5 line-clamp-2 text-sm text-muted-foreground">{{ q.description }}</p>
                        <div class="mt-2 flex items-center gap-3 text-xs">
                            <span class="text-muted-foreground">{{ q.question_count }} questions</span>
                            <span v-if="q.estimated_minutes" class="text-muted-foreground">~{{ q.estimated_minutes }} min</span>
                            <span :class="statusClass(q.response_status)" class="font-medium">{{ statusLabel(q.response_status) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="questionnaires.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1 pt-2"
            aria-label="Pagination"
        >
            <Link
                v-for="link in questionnaires.links"
                :key="link.label"
                :href="link.url ?? '#'"
                preserve-scroll
                preserve-state
                v-html="link.label"
                class="min-h-[40px] min-w-[40px] rounded-full px-3 py-2 text-sm font-semibold transition-colors"
                :class="[
                    link.active ? 'bg-primary text-white shadow' : 'card-premium text-foreground',
                    !link.url ? 'pointer-events-none opacity-40' : '',
                ]"
            />
        </div>

        <p
            v-if="questionnaires.total > 0"
            class="text-center text-xs text-muted-foreground"
        >
            Showing {{ questionnaires.from }}–{{ questionnaires.to }} of {{ questionnaires.total }}
        </p>
    </div>
</template>
