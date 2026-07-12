<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

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

function setFilter(value: string) {
    router.get(route('timeline.index'), { filter: value }, { preserveState: true });
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

const typeConfig: Record<string, { colour: string; bg: string }> = {
    questionnaire: { colour: 'text-purple-600', bg: 'bg-purple-50' },
    plan: { colour: 'text-pink-600', bg: 'bg-pink-50' },
    moment: { colour: 'text-emerald-600', bg: 'bg-emerald-50' },
    love_note: { colour: 'text-rose-600', bg: 'bg-rose-50' },
    calendar: { colour: 'text-blue-600', bg: 'bg-blue-50' },
    achievement: { colour: 'text-yellow-600', bg: 'bg-yellow-50' },
};
</script>

<template>
    <AppLayout>
        <Head title="Timeline" />

        <div class="min-h-screen bg-[#FFF7FB] px-4 py-8">
            <div class="mx-auto max-w-lg">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900">⏳ Timeline</h1>
                    <p class="text-sm text-gray-500">Your relationship story</p>
                </div>

                <!-- Filters -->
                <div class="mb-6 flex gap-2 overflow-x-auto pb-2">
                    <button
                        v-for="f in filters"
                        :key="f.value"
                        class="flex-shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-all"
                        :class="filter === f.value ? 'bg-pink-500 text-white shadow-md' : 'bg-white text-gray-600 shadow'"
                        @click="setFilter(f.value)"
                    >
                        {{ f.label }}
                    </button>
                </div>

                <!-- Timeline Entries -->
                <div v-if="entries.length > 0" class="relative">
                    <!-- Vertical line -->
                    <div class="absolute left-6 top-0 h-full w-0.5 bg-gradient-to-b from-pink-200 to-purple-200" />

                    <div class="space-y-4">
                        <div
                            v-for="entry in entries"
                            :key="entry.id"
                            class="relative flex gap-4"
                        >
                            <!-- Dot -->
                            <div
                                class="relative z-10 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full text-xl shadow-md"
                                :class="typeConfig[entry.type]?.bg ?? 'bg-gray-50'"
                            >
                                {{ entry.emoji }}
                            </div>

                            <!-- Card -->
                            <component
                                :is="entry.link ? Link : 'div'"
                                :href="entry.link ?? undefined"
                                class="flex-1 overflow-hidden rounded-3xl bg-white p-4 shadow-xl transition-all"
                                :class="entry.link ? 'hover:scale-[1.02] hover:shadow-2xl' : ''"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900">{{ entry.title }}</div>
                                        <div class="mt-0.5 text-xs text-gray-400">{{ formatDate(entry.occurred_at) }}</div>
                                        <p v-if="entry.description" class="mt-1 line-clamp-2 text-sm text-gray-600">{{ entry.description }}</p>
                                    </div>
                                    <div v-if="entry.compatibility_score !== undefined" class="flex-shrink-0 text-right">
                                        <div class="text-lg font-semibold text-pink-500">{{ entry.compatibility_score }}%</div>
                                        <div class="text-xs text-gray-400">match</div>
                                    </div>
                                    <div v-if="entry.points !== undefined" class="flex-shrink-0 text-right">
                                        <div class="text-lg font-semibold text-yellow-500">+{{ entry.points }}</div>
                                        <div class="text-xs text-gray-400">pts</div>
                                    </div>
                                </div>
                            </component>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-16 text-center">
                    <div class="mb-4 text-6xl">⏳</div>
                    <h3 class="text-xl font-semibold text-gray-900">Nothing here yet</h3>
                    <p class="mt-2 text-gray-500">Your story will appear here as you use the app</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
