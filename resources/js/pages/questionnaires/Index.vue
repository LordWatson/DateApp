<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
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
    is_solo: boolean;
    response_status: string | null;
    partner_paired: boolean;
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

const filterOptions: ReadonlyArray<{ value: FilterValue; label: string }> = [
    { value: 'all', label: 'All' },
    { value: 'couples', label: 'Couples' },
    { value: 'solo', label: 'Solo' },
    { value: 'intimacy', label: 'Intimacy' },
    { value: 'seasonal', label: 'Seasonal' },
];

function applyFilter(value: FilterValue): void {
    router.get(
        '/questionnaires',
        { filter: value },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

function statusLabel(q: Questionnaire): string {
    if (q.response_status === 'in_progress') {
        return 'In progress';
    }

    if (q.response_status === 'completed') {
        if (q.is_solo) {
            return '';
        }

        if (q.partner_paired) {
            return '';
        }

        return 'Pending partner';
    }

    return 'Not started';
}

function statusClass(q: Questionnaire): string {
    if (q.response_status === 'in_progress') {
        return 'text-warning';
    }

    if (q.response_status === 'completed') {
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

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            title="Plan"
            description="Choose a questionnaire to plan your evening."
        />

        <div class="space-y-4">
            <!-- Filter pills -->
            <div
                class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1"
                role="group"
                aria-label="Filter questionnaires"
            >
                <button
                    v-for="option in filterOptions"
                    :key="option.value"
                    type="button"
                    class="chip focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                    :aria-pressed="props.filters.filter === option.value"
                    @click="applyFilter(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>

            <div v-if="questionnaires.data.length === 0" class="card-premium">
                <EmptyState
                    emoji="📭"
                    title="No questionnaires found"
                    description="Try a different filter above."
                />
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="q in questionnaires.data"
                    :key="q.id"
                    role="link"
                    tabindex="0"
                    class="flex card-hover cursor-pointer items-center gap-4 card-premium p-5 focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none active:scale-[0.98]"
                    @click="openQuestionnaire(q.slug)"
                    @keydown.enter="openQuestionnaire(q.slug)"
                >
                    <div
                        class="icon-tile h-14 w-14 self-start text-3xl"
                        aria-hidden="true"
                    >
                        {{ q.emoji ?? '💕' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2
                            class="text-lg leading-tight font-semibold text-foreground"
                        >
                            {{ q.title }}
                        </h2>
                        <p
                            v-if="q.description"
                            class="mt-1 line-clamp-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ q.description }}
                        </p>
                        <div
                            class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                        >
                            <span class="tabular-nums"
                                >{{ q.question_count }} questions</span
                            >
                            <span
                                v-if="q.estimated_minutes"
                                class="tabular-nums"
                                >~{{ q.estimated_minutes }} min</span
                            >
                            <span
                                v-if="statusLabel(q)"
                                :class="statusClass(q)"
                                class="font-medium"
                                >{{ statusLabel(q) }}</span
                            >
                        </div>
                    </div>
                    <ChevronRight
                        class="size-5 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="questionnaires.last_page > 1 || questionnaires.total > 0"
            class="space-y-3"
        >
            <!-- Pagination -->
            <nav
                v-if="questionnaires.last_page > 1"
                class="flex flex-wrap items-center justify-center gap-1.5"
                aria-label="Pagination"
            >
                <Link
                    v-for="link in questionnaires.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    preserve-scroll
                    preserve-state
                    class="chip min-w-10 justify-center tabular-nums"
                    :class="!link.url ? 'pointer-events-none opacity-40' : ''"
                    :data-active="link.active"
                    :aria-current="link.active ? 'page' : undefined"
                >
                    <span v-html="link.label" />
                </Link>
            </nav>

            <p
                v-if="questionnaires.total > 0"
                class="text-center text-xs text-muted-foreground tabular-nums"
            >
                Showing {{ questionnaires.from }}–{{ questionnaires.to }} of
                {{ questionnaires.total }}
            </p>
        </div>
    </div>
</template>
