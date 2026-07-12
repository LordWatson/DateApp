<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

interface Insights {
    total_questionnaires: number;
    total_plans: number;
    average_compatibility: number;
    love_notes_sent: number;
    love_notes_received: number;
    current_streak: number;
    longest_streak: number;
    most_completed_month: string | null;
    favourite_theme: string | null;
    most_selected_atmosphere: string | null;
    compatibility_over_time: Array<{ month: string; score: number }>;
    moments_count: number;
    calendar_events_count: number;
}

defineProps<{ insights: Insights }>();

const statCards = (insights: Insights) => [
    { emoji: '📋', label: 'Questionnaires', value: insights.total_questionnaires, colour: 'from-purple-400 to-purple-600' },
    { emoji: '🌙', label: 'Plans Generated', value: insights.total_plans, colour: 'from-pink-400 to-pink-600' },
    { emoji: '💕', label: 'Avg Compatibility', value: `${insights.average_compatibility}%`, colour: 'from-rose-400 to-pink-500' },
    { emoji: '🔥', label: 'Current Streak', value: `${insights.current_streak}d`, colour: 'from-orange-400 to-red-500' },
    { emoji: '🏆', label: 'Longest Streak', value: `${insights.longest_streak}d`, colour: 'from-yellow-400 to-orange-500' },
    { emoji: '💌', label: 'Notes Sent', value: insights.love_notes_sent, colour: 'from-rose-300 to-rose-500' },
    { emoji: '📖', label: 'Moments', value: insights.moments_count, colour: 'from-emerald-400 to-teal-500' },
    { emoji: '📅', label: 'Calendar Events', value: insights.calendar_events_count, colour: 'from-blue-400 to-indigo-500' },
];
</script>

<template>
        <Head title="Relationship Insights" />

        <div class="min-h-screen bg-[#FFF7FB] px-4 py-8">
            <div class="mx-auto max-w-lg">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <div class="mb-2 text-5xl">📊</div>
                    <h1 class="text-2xl font-semibold text-gray-900">Relationship Insights</h1>
                    <p class="text-sm text-gray-500">Your love story in numbers</p>
                </div>

                <!-- Stat Cards Grid -->
                <div class="mb-8 grid grid-cols-2 gap-4">
                    <div
                        v-for="card in statCards(insights)"
                        :key="card.label"
                        class="relative overflow-hidden rounded-3xl bg-white p-5 shadow-xl"
                    >
                        <div :class="`absolute inset-0 bg-gradient-to-br ${card.colour} opacity-5`" />
                        <div class="relative">
                            <div class="mb-2 text-3xl">{{ card.emoji }}</div>
                            <div class="text-2xl font-semibold text-gray-900">{{ card.value }}</div>
                            <div class="text-sm text-gray-500">{{ card.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- Compatibility Over Time -->
                <div v-if="insights.compatibility_over_time.length > 0" class="mb-8 rounded-3xl bg-white p-6 shadow-xl">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">💕 Compatibility Over Time</h2>
                    <div class="flex items-end gap-2" style="height: 120px">
                        <div
                            v-for="point in insights.compatibility_over_time"
                            :key="point.month"
                            class="group relative flex flex-1 flex-col items-center justify-end"
                        >
                            <div class="absolute -top-6 hidden text-xs font-semibold text-pink-500 group-hover:block">
                                {{ point.score }}%
                            </div>
                            <div
                                class="w-full rounded-t-lg bg-gradient-to-t from-pink-500 to-purple-400 transition-all duration-500"
                                :style="{ height: `${point.score}%` }"
                            />
                            <div class="mt-1 text-xs text-gray-400">{{ point.month.slice(5) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Highlights -->
                <div class="space-y-4">
                    <div v-if="insights.favourite_theme" class="rounded-3xl bg-white p-5 shadow-xl">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-pink-50 text-2xl">🌙</div>
                            <div>
                                <div class="text-sm text-gray-500">Favourite Theme</div>
                                <div class="font-semibold text-gray-900 capitalize">{{ insights.favourite_theme }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="insights.most_selected_atmosphere" class="rounded-3xl bg-white p-5 shadow-xl">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-2xl">✨</div>
                            <div>
                                <div class="text-sm text-gray-500">Favourite Atmosphere</div>
                                <div class="font-semibold text-gray-900 capitalize">{{ insights.most_selected_atmosphere }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="insights.most_completed_month" class="rounded-3xl bg-white p-5 shadow-xl">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-2xl">📅</div>
                            <div>
                                <div class="text-sm text-gray-500">Most Active Month</div>
                                <div class="font-semibold text-gray-900">{{ insights.most_completed_month }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-5 shadow-xl">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-2xl">💌</div>
                            <div>
                                <div class="text-sm text-gray-500">Love Notes</div>
                                <div class="font-semibold text-gray-900">{{ insights.love_notes_sent }} sent · {{ insights.love_notes_received }} received</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="insights.total_questionnaires === 0" class="mt-8 py-8 text-center">
                    <p class="text-gray-500">Complete questionnaires together to see your insights grow 💕</p>
                </div>
            </div>
        </div>
</template>
