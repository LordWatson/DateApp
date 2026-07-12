<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

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

    return text.replace(regex, '<mark class="bg-yellow-100 text-yellow-800 rounded px-0.5">$1</mark>');
}
</script>

<template>
    <AppLayout>
        <Head title="Search" />

        <div class="min-h-screen bg-[#FFF7FB] px-4 py-8">
            <div class="mx-auto max-w-lg">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="mb-4 text-2xl font-semibold text-gray-900">🔍 Search</h1>
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="search"
                            class="w-full rounded-3xl border border-gray-200 bg-white px-5 py-4 pr-12 text-gray-900 shadow-xl focus:border-pink-400 focus:outline-none"
                            placeholder="Search everything..."
                            autofocus
                        />
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</div>
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
                                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">
                                    {{ groupLabels[key as string] ?? key }}
                                </h2>
                                <div class="space-y-2">
                                    <Link
                                        v-for="item in group"
                                        :key="item.id"
                                        :href="item.url"
                                        class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-md transition-all hover:scale-[1.02] hover:shadow-lg"
                                    >
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-pink-50 text-xl">
                                            {{ item.emoji }}
                                        </div>
                                        <div class="flex-1 overflow-hidden">
                                            <div
                                                class="font-semibold text-gray-900"
                                                v-html="highlight(item.title, searchQuery)"
                                            />
                                            <div
                                                v-if="item.description"
                                                class="line-clamp-1 text-sm text-gray-500"
                                                v-html="highlight(item.description, searchQuery)"
                                            />
                                        </div>
                                        <div class="text-gray-400">›</div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Results -->
                    <div v-else class="py-16 text-center">
                        <div class="mb-4 text-6xl">🔍</div>
                        <h3 class="text-xl font-semibold text-gray-900">No results found</h3>
                        <p class="mt-2 text-gray-500">Try a different search term</p>
                    </div>
                </div>

                <!-- Initial State -->
                <div v-else class="py-16 text-center">
                    <div class="mb-4 text-6xl">✨</div>
                    <h3 class="text-xl font-semibold text-gray-900">Search everything</h3>
                    <p class="mt-2 text-gray-500">Plans, moments, love notes, questionnaires and more</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
