<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

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

const props = defineProps<{ insights: Insights }>();

const statCards = [
    { emoji: '📋', label: 'Questionnaires', value: props.insights.total_questionnaires, colour: 'from-purple-400 to-purple-600' },
    { emoji: '🌙', label: 'Plans Generated', value: props.insights.total_plans, colour: 'from-pink-400 to-pink-600' },
    { emoji: '💕', label: 'Avg Compatibility', value: `${props.insights.average_compatibility}%`, colour: 'from-rose-400 to-pink-500' },
    { emoji: '🔥', label: 'Current Streak', value: `${props.insights.current_streak}d`, colour: 'from-orange-400 to-red-500' },
    { emoji: '🏆', label: 'Longest Streak', value: `${props.insights.longest_streak}d`, colour: 'from-yellow-400 to-orange-500' },
    { emoji: '💌', label: 'Notes Sent', value: props.insights.love_notes_sent, colour: 'from-rose-300 to-rose-500' },
    { emoji: '📖', label: 'Moments', value: props.insights.moments_count, colour: 'from-emerald-400 to-teal-500' },
    { emoji: '📅', label: 'Calendar Events', value: props.insights.calendar_events_count, colour: 'from-blue-400 to-indigo-500' },
];
</script>

<template>
    <Head title="Relationship Insights" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <!-- Header -->
        <div class="text-center">
            <div class="mb-2 text-5xl" aria-hidden="true">📊</div>
            <h1 class="text-2xl font-semibold text-foreground">Relationship Insights</h1>
            <p class="text-sm text-muted-foreground">Your love story in numbers</p>
        </div>

        <!-- Stat Cards Grid -->
        <div class="grid grid-cols-2 gap-4">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="card-premium relative overflow-hidden p-5"
            >
                <div :class="`absolute inset-0 bg-gradient-to-br ${card.colour} opacity-5`" aria-hidden="true" />
                <div class="relative">
                    <div class="mb-2 text-3xl" aria-hidden="true">{{ card.emoji }}</div>
                    <p class="text-2xl font-semibold text-foreground">{{ card.value }}</p>
                    <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                </div>
            </div>
        </div>

        <!-- Compatibility Over Time -->
        <div v-if="insights.compatibility_over_time.length > 0" class="card-premium p-6">
            <h2 class="mb-4 text-lg font-semibold text-foreground">💕 Compatibility Over Time</h2>
            <div class="flex items-end gap-2" style="height: 120px" role="img" aria-label="Compatibility chart over time">
                <div
                    v-for="point in insights.compatibility_over_time"
                    :key="point.month"
                    class="group relative flex flex-1 flex-col items-center justify-end"
                >
                    <div class="absolute -top-6 hidden text-xs font-semibold text-primary group-hover:block">
                        {{ point.score }}%
                    </div>
                    <div
                        class="w-full rounded-t-lg transition-all duration-500 gradient-primary"
                        :style="{ height: `${point.score}%` }"
                    />
                    <div class="mt-1 text-xs text-muted-foreground">{{ point.month.slice(5) }}</div>
                </div>
            </div>
        </div>

        <!-- Highlights -->
        <div class="space-y-4">
            <div v-if="insights.favourite_theme" class="card-premium p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-2xl" aria-hidden="true">🌙</div>
                    <div>
                        <p class="text-sm text-muted-foreground">Favourite Theme</p>
                        <p class="font-semibold capitalize text-foreground">{{ insights.favourite_theme }}</p>
                    </div>
                </div>
            </div>

            <div v-if="insights.most_selected_atmosphere" class="card-premium p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-secondary/10 text-2xl" aria-hidden="true">✨</div>
                    <div>
                        <p class="text-sm text-muted-foreground">Favourite Atmosphere</p>
                        <p class="font-semibold capitalize text-foreground">{{ insights.most_selected_atmosphere }}</p>
                    </div>
                </div>
            </div>

            <div v-if="insights.most_completed_month" class="card-premium p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-success/10 text-2xl" aria-hidden="true">📅</div>
                    <div>
                        <p class="text-sm text-muted-foreground">Most Active Month</p>
                        <p class="font-semibold text-foreground">{{ insights.most_completed_month }}</p>
                    </div>
                </div>
            </div>

            <div class="card-premium p-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-2xl" aria-hidden="true">💌</div>
                    <div>
                        <p class="text-sm text-muted-foreground">Love Notes</p>
                        <p class="font-semibold text-foreground">
                            {{ insights.love_notes_sent }} sent · {{ insights.love_notes_received }} received
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-if="insights.total_questionnaires === 0"
            emoji="💕"
            title="Your story is just beginning"
            description="Complete questionnaires together to see your insights grow."
        />
    </div>
</template>
