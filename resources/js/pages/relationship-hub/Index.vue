<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

interface Props {
    partner: { id: number; name: string; avatar: string | null } | null;
    stats: { current_streak: number; longest_streak: number; average_compatibility: number };
    upcoming_events: Array<{ id: number; title: string; emoji: string; date: string; colour: string }>;
    recent_moments: Array<{ id: number; title: string; mood: string | null; date: string; is_favourite: boolean }>;
    achievement_progress: { total: number; unlocked: number; points: number; percentage: number };
    seasonal_questionnaires: Array<{ id: number; title: string; emoji: string; slug: string; description: string }>;
}

const props = defineProps<Props>();

const cards = [
    { emoji: '❤️', title: 'Relationship Overview', description: 'Your love story at a glance', href: route('insights.index'), colour: 'from-pink-500 to-rose-500' },
    { emoji: '🔥', title: 'Current Streak', description: `${props.stats.current_streak} days`, href: route('dashboard'), colour: 'from-orange-400 to-red-500' },
    { emoji: '🏆', title: 'Longest Streak', description: `${props.stats.longest_streak} days`, href: route('dashboard'), colour: 'from-yellow-400 to-orange-500' },
    { emoji: '💕', title: 'Compatibility Average', description: `${props.stats.average_compatibility}%`, href: route('insights.index'), colour: 'from-pink-400 to-purple-500' },
    { emoji: '📅', title: 'Upcoming Events', description: `${props.upcoming_events.length} upcoming`, href: route('calendar.index'), colour: 'from-blue-400 to-indigo-500' },
    { emoji: '📖', title: 'Recent Moments', description: `${props.recent_moments.length} captured`, href: route('moments.index'), colour: 'from-emerald-400 to-teal-500' },
    { emoji: '🎯', title: 'Achievement Progress', description: `${props.achievement_progress.unlocked}/${props.achievement_progress.total} unlocked`, href: route('achievements.index'), colour: 'from-violet-400 to-purple-600' },
    { emoji: '🎁', title: 'Seasonal Events', description: 'Special questionnaires', href: route('questionnaires.index'), colour: 'from-pink-300 to-rose-400' },
];
</script>

<template>
    <AppLayout>
        <Head title="Relationship Hub" />

        <div class="min-h-screen bg-[#FFF7FB] px-4 py-8">
            <div class="mx-auto max-w-lg">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <div class="mb-2 text-5xl">💑</div>
                    <h1 class="text-3xl font-semibold text-gray-900">Relationship Hub</h1>
                    <p class="mt-1 text-gray-500">
                        Everything about your relationship, in one place
                        <span v-if="partner"> with {{ partner.name }}</span>
                    </p>
                </div>

                <!-- Hub Cards Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <Link
                        v-for="card in cards"
                        :key="card.title"
                        :href="card.href"
                        class="group relative overflow-hidden rounded-3xl bg-white p-5 shadow-xl transition-all duration-300 hover:scale-105 hover:shadow-2xl"
                    >
                        <div :class="`absolute inset-0 bg-gradient-to-br ${card.colour} opacity-5 transition-opacity group-hover:opacity-10`" />
                        <div class="relative">
                            <div class="mb-3 text-3xl">{{ card.emoji }}</div>
                            <div class="text-sm font-semibold text-gray-900">{{ card.title }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ card.description }}</div>
                        </div>
                    </Link>
                </div>

                <!-- Upcoming Events Preview -->
                <div v-if="upcoming_events.length > 0" class="mt-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">📅 Upcoming Events</h2>
                    <div class="space-y-3">
                        <div
                            v-for="event in upcoming_events"
                            :key="event.id"
                            class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-md"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl text-xl"
                                :style="{ backgroundColor: event.colour + '20' }"
                            >
                                {{ event.emoji }}
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">{{ event.title }}</div>
                                <div class="text-sm text-gray-500">{{ new Date(event.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long' }) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Moments Preview -->
                <div v-if="recent_moments.length > 0" class="mt-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">📖 Recent Moments</h2>
                    <div class="space-y-3">
                        <div
                            v-for="moment in recent_moments"
                            :key="moment.id"
                            class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-md"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-50 text-xl">
                                {{ moment.is_favourite ? '⭐' : '📖' }}
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">{{ moment.title }}</div>
                                <div class="text-sm text-gray-500">{{ moment.mood ?? new Date(moment.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long' }) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Achievement Progress -->
                <div class="mt-8 rounded-3xl bg-white p-6 shadow-xl">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">🎯 Achievement Progress</h2>
                        <Link :href="route('achievements.index')" class="text-sm font-semibold text-pink-500">View all</Link>
                    </div>
                    <div class="mb-2 flex justify-between text-sm text-gray-600">
                        <span>{{ achievement_progress.unlocked }} / {{ achievement_progress.total }} unlocked</span>
                        <span>{{ achievement_progress.points }} pts</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-pink-500 to-purple-600 transition-all duration-700"
                            :style="{ width: `${achievement_progress.percentage}%` }"
                        />
                    </div>
                    <div class="mt-2 text-right text-sm font-semibold text-pink-500">{{ achievement_progress.percentage }}%</div>
                </div>

                <!-- Seasonal Questionnaires -->
                <div v-if="seasonal_questionnaires.length > 0" class="mt-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">🎁 Seasonal Questionnaires</h2>
                    <div class="space-y-3">
                        <Link
                            v-for="q in seasonal_questionnaires"
                            :key="q.id"
                            :href="route('questionnaires.show', { questionnaire: q.slug })"
                            class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-md transition-all hover:scale-[1.02] hover:shadow-lg"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-50 text-xl">
                                {{ q.emoji }}
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">{{ q.title }}</div>
                                <div class="line-clamp-1 text-sm text-gray-500">{{ q.description }}</div>
                            </div>
                            <div class="text-gray-400">›</div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
