<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

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
    router.get(route('timeline.index'), { filter: value }, { preserveState: true });
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

const typeConfig: Record<string, { bg: string }> = {
    questionnaire: { bg: 'bg-secondary/10' },
    plan: { bg: 'bg-primary/10' },
    moment: { bg: 'bg-success/10' },
    love_note: { bg: 'bg-primary/10' },
    calendar: { bg: 'bg-blue-500/10' },
    achievement: { bg: 'bg-warning/10' },
};
</script>

<template>
    <Head title="Timeline" />

    <div class="space-y-4 px-4 py-6 pb-24">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-semibold text-foreground">⏳ Timeline</h1>
            <p class="text-sm text-muted-foreground">Your relationship story</p>
        </div>

        <!-- Filters -->
        <div class="flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Filter timeline entries">
            <button
                v-for="f in filters"
                :key="f.value"
                role="tab"
                :aria-selected="filter === f.value"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                :class="filter === f.value ? 'gradient-primary text-white shadow-md' : 'bg-card text-muted-foreground shadow'"
                @click="setFilter(f.value)"
            >
                {{ f.label }}
            </button>
        </div>

        <!-- Timeline Entries -->
        <div v-if="entries.length > 0" class="relative">
            <!-- Vertical line -->
            <div class="absolute left-6 top-0 h-full w-0.5 bg-gradient-to-b from-primary/30 to-secondary/30" aria-hidden="true" />

            <div class="space-y-4">
                <div
                    v-for="entry in entries"
                    :key="entry.id"
                    class="relative flex gap-4"
                >
                    <!-- Dot -->
                    <div
                        class="relative z-10 flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-xl shadow-md"
                        :class="typeConfig[entry.type]?.bg ?? 'bg-muted'"
                        aria-hidden="true"
                    >
                        {{ entry.emoji }}
                    </div>

                    <!-- Card -->
                    <component
                        :is="entry.link ? Link : 'div'"
                        :href="entry.link ?? undefined"
                        class="card-premium flex-1 overflow-hidden p-4 transition-all"
                        :class="entry.link ? 'card-hover' : ''"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-foreground">{{ entry.title }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ formatDate(entry.occurred_at) }}</p>
                                <p v-if="entry.description" class="mt-1 line-clamp-2 text-sm text-foreground/80">
                                    {{ entry.description }}
                                </p>
                            </div>
                            <div v-if="entry.compatibility_score !== undefined" class="shrink-0 text-right">
                                <p class="text-lg font-semibold text-primary">{{ entry.compatibility_score }}%</p>
                                <p class="text-xs text-muted-foreground">match</p>
                            </div>
                            <div v-if="entry.points !== undefined" class="shrink-0 text-right">
                                <p class="text-lg font-semibold text-warning">+{{ entry.points }}</p>
                                <p class="text-xs text-muted-foreground">pts</p>
                            </div>
                        </div>
                    </component>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            emoji="⏳"
            title="Nothing here yet"
            description="Your story will appear here as you use the app together."
        />
    </div>
</template>
