<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { index as timelineIndex } from '@/routes/timeline';

defineOptions({ layout: MobileLayout });

interface TimelineEntry {
    id: string;
    type: string;
    title: string;
    description: string | null;
    emoji: string | null;
    occurred_at: string;
    link: string | null;
    compatibility_score?: number;
    mood?: string;
    is_favourite?: boolean;
    colour?: string;
    points?: number;
}

interface Filter {
    value: string;
    label: string;
}

defineProps<{
    entries: TimelineEntry[];
    filter: string;
    filters: Filter[];
}>();

function setFilter(value: string): void {
    router.get(timelineIndex().url, { filter: value }, { preserveState: true });
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Timeline" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader title="Timeline" description="Your relationship story" />

        <!-- Filters -->
        <div
            class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1"
            role="tablist"
            aria-label="Filter timeline entries"
        >
            <button
                v-for="f in filters"
                :key="f.value"
                type="button"
                role="tab"
                :aria-selected="filter === f.value"
                class="chip focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                @click="setFilter(f.value)"
            >
                {{ f.label }}
            </button>
        </div>

        <!-- Timeline entries -->
        <div v-if="entries.length > 0" class="relative">
            <!-- Vertical line -->
            <div
                class="absolute top-0 left-6 h-full w-px bg-border"
                aria-hidden="true"
            />

            <div class="space-y-4">
                <div
                    v-for="entry in entries"
                    :key="entry.id"
                    class="relative flex gap-4"
                >
                    <!-- Marker -->
                    <div
                        class="relative z-10 icon-tile h-12 w-12 text-xl"
                        aria-hidden="true"
                    >
                        {{ entry.emoji }}
                    </div>

                    <!-- Card -->
                    <component
                        :is="entry.link ? Link : 'div'"
                        :href="entry.link ?? undefined"
                        class="min-w-0 flex-1 card-premium p-4"
                        :class="entry.link ? 'card-hover' : ''"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-foreground">
                                    {{ entry.title }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ formatDate(entry.occurred_at) }}
                                </p>
                                <p
                                    v-if="entry.description"
                                    class="mt-1.5 line-clamp-2 text-sm text-muted-foreground"
                                >
                                    {{ entry.description }}
                                </p>
                            </div>
                            <div
                                v-if="entry.compatibility_score !== undefined"
                                class="shrink-0 text-right"
                            >
                                <p
                                    class="font-display text-xl leading-tight font-medium text-primary tabular-nums"
                                >
                                    {{ entry.compatibility_score }}%
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    match
                                </p>
                            </div>
                            <div
                                v-if="entry.points !== undefined"
                                class="shrink-0 text-right"
                            >
                                <p
                                    class="font-display text-xl leading-tight font-medium text-gold tabular-nums"
                                >
                                    +{{ entry.points }}
                                </p>
                                <p class="text-xs text-muted-foreground">pts</p>
                            </div>
                            <ChevronRight
                                v-if="entry.link"
                                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </div>
                    </component>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="card-premium">
            <EmptyState
                emoji="⏳"
                title="Nothing here yet"
                description="Your story will appear here as you use the app together."
            />
        </div>
    </div>
</template>
