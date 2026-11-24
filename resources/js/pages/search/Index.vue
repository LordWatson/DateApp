<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { index as searchIndex } from '@/routes/search';

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
        router.get(
            searchIndex().url,
            { q: val },
            { preserveState: true, replace: true },
        );
    }, 400);
});

const groupLabels: Record<string, string> = {
    plans: 'Plans',
    moments: 'Moments',
    love_notes: 'Love notes',
    questionnaires: 'Questionnaires',
    saved_profiles: 'Saved profiles',
    calendar_events: 'Calendar events',
    achievements: 'Achievements',
};

const hasResults = Object.values(props.results).some(
    (group) => group && group.length > 0,
);

function highlight(text: string | null, query: string): string {
    if (!text || !query) {
        return text ?? '';
    }

    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escaped})`, 'gi');

    return text.replace(
        regex,
        '<mark class="rounded bg-gold-soft/30 px-0.5 text-foreground">$1</mark>',
    );
}
</script>

<template>
    <Head title="Search" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <!-- Header and search input -->
        <div class="space-y-5">
            <PageHeader title="Search" />

            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <input
                    v-model="searchQuery"
                    type="search"
                    class="input-premium pl-11"
                    placeholder="Search everything..."
                    autofocus
                    aria-label="Search"
                />
            </div>
        </div>

        <!-- Results -->
        <template v-if="searchQuery.length >= 2">
            <div v-if="hasResults" class="space-y-8">
                <template v-for="(group, key) in results" :key="key">
                    <section v-if="group && group.length > 0" class="space-y-3">
                        <SectionHeader
                            :title="groupLabels[key as string] ?? key"
                        />

                        <div class="card-premium p-2">
                            <Link
                                v-for="item in group"
                                :key="item.id"
                                :href="item.url"
                                class="list-row"
                            >
                                <span
                                    class="icon-tile h-11 w-11 text-xl"
                                    aria-hidden="true"
                                    >{{ item.emoji }}</span
                                >
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate font-medium text-foreground"
                                        v-html="
                                            highlight(item.title, searchQuery)
                                        "
                                    />
                                    <p
                                        v-if="item.description"
                                        class="line-clamp-1 text-xs text-muted-foreground"
                                        v-html="
                                            highlight(
                                                item.description,
                                                searchQuery,
                                            )
                                        "
                                    />
                                </div>
                                <ChevronRight
                                    class="size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </Link>
                        </div>
                    </section>
                </template>
            </div>

            <!-- No results -->
            <div v-else class="card-premium">
                <EmptyState
                    emoji="🔍"
                    title="No results found"
                    description="Try a different search term or explore another section."
                />
            </div>
        </template>

        <!-- Initial state -->
        <div v-else class="card-premium">
            <EmptyState
                emoji="✨"
                title="Search everything"
                description="Plans, moments, love notes, questionnaires and more."
            />
        </div>
    </div>
</template>
