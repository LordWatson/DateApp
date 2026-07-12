<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface SearchResult {
    id: number;
    title: string;
    description: string | null;
    emoji: string;
    url: string;
}

interface Results {
    plans?: SearchResult[];
    moments?: SearchResult[];
    love_notes?: SearchResult[];
    questionnaires?: SearchResult[];
    saved_profiles?: SearchResult[];
    calendar_events?: SearchResult[];
    achievements?: SearchResult[];
}

const props = defineProps<{
    query: string;
    results: Results;
}>();

const searchQuery = ref(props.query);
let debounceTimer: ReturnType<typeof setTimeout>;

watch(searchQuery, (val) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('search.index'), { q: val }, { preserveState: true, replace: true });
    }, 400);
});

const groupLabels: Record<string, string> = {
    plans: '🌙 Plans',
    moments: '📖 Moments',
    love_notes: '💌 Love Notes',
    questionnaires: '📋 Questionnaires',
    saved_profiles: '💾 Saved Profiles',
    calendar_events: '📅 Calendar Events',
    achievements: '🏆 Achievements',
};

const hasResults = Object.values(props.results).some((group) => group && group.length > 0);

function highlight(text: string | null, query: string): string {
    if (!text || !query) {
        return text ?? '';
    }

    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escaped})`, 'gi');

    return text.replace(regex, '<mark class="rounded bg-warning/20 px-0.5 text-warning-foreground">$1</mark>');
}
</script>

<template>
    <Head title="Search" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <!-- Header & Search Input -->
        <div>
            <h1 class="mb-4 text-2xl font-semibold text-foreground">🔍 Search</h1>
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="search"
                    class="w-full rounded-3xl border border-border bg-card px-5 py-4 pr-12 text-foreground placeholder:text-muted-foreground shadow-xl focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    placeholder="Search everything..."
                    autofocus
                    aria-label="Search"
                />
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground" aria-hidden="true">🔍</span>
            </div>
        </div>

        <!-- Results -->
        <div v-if="searchQuery.length >= 2">
            <div v-if="hasResults" class="space-y-6">
                <div
                    v-for="(group, key) in results"
                    :key="key"
                >
                    <div v-if="group && group.length > 0">
                        <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            {{ groupLabels[key as string] ?? key }}
                        </h2>
                        <div class="space-y-2">
                            <Link
                                v-for="item in group"
                                :key="item.id"
                                :href="item.url"
                                class="card-premium card-hover flex items-center gap-3 p-4"
                            >
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-xl" aria-hidden="true">
                                    {{ item.emoji }}
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <p
                                        class="font-semibold text-foreground"
                                        v-html="highlight(item.title, searchQuery)"
                                    />
                                    <p
                                        v-if="item.description"
                                        class="line-clamp-1 text-sm text-muted-foreground"
                                        v-html="highlight(item.description, searchQuery)"
                                    />
                                </div>
                                <span class="text-muted-foreground" aria-hidden="true">›</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <EmptyState
                v-else
                emoji="🔍"
                title="No results found"
                description="Try a different search term or explore another section."
            />
        </div>

        <!-- Initial State -->
        <EmptyState
            v-else
            emoji="✨"
            title="Search everything"
            description="Plans, moments, love notes, questionnaires and more."
        />
    </div>
</template>
