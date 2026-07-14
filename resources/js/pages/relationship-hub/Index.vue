<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

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
    { emoji: '❤️', title: 'Relationship Insights', description: 'Your love story in numbers', href: '/insights', colour: 'from-pink-500 to-rose-500' },
    { emoji: '🔥', title: 'Current Streak', description: `${props.stats.current_streak} days`, href: '/dashboard', colour: 'from-orange-400 to-red-500' },
    { emoji: '🏆', title: 'Longest Streak', description: `${props.stats.longest_streak} days`, href: '/dashboard', colour: 'from-yellow-400 to-orange-500' },
    { emoji: '💕', title: 'Compatibility', description: `${props.stats.average_compatibility}%`, href: '/insights', colour: 'from-pink-400 to-purple-500' },
    { emoji: '📅', title: 'Upcoming Events', description: `${props.upcoming_events.length} upcoming`, href: '/calendar', colour: 'from-blue-400 to-indigo-500' },
    { emoji: '📖', title: 'Recent Moments', description: `${props.recent_moments.length} captured`, href: '/moments', colour: 'from-emerald-400 to-teal-500' },
    { emoji: '🎯', title: 'Achievements', description: `${props.achievement_progress.unlocked}/${props.achievement_progress.total} unlocked`, href: '/achievements', colour: 'from-violet-400 to-purple-600' },
    { emoji: '🎁', title: 'Seasonal Events', description: 'Special questionnaires', href: '/questionnaires', colour: 'from-pink-300 to-rose-400' },
    { emoji: '💌', title: 'Love Notes', description: 'Sweet messages to your partner', href: '/love-notes', colour: 'from-pink-500 to-purple-500' },
];
</script>

<template>
    <Head title="Relationship Hub" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <!-- Header -->
        <div class="text-center">
            <div class="mb-2 text-5xl" aria-hidden="true">💑</div>
            <h1 class="text-2xl font-semibold text-foreground">Relationship Hub</h1>
            <p class="text-sm text-muted-foreground">
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
                class="card-premium card-hover group relative overflow-hidden p-5"
            >
                <div :class="`absolute inset-0 bg-gradient-to-br ${card.colour} opacity-5 transition-opacity group-hover:opacity-10`" aria-hidden="true" />
                <div class="relative">
                    <div class="mb-3 text-3xl" aria-hidden="true">{{ card.emoji }}</div>
                    <p class="text-sm font-semibold text-foreground">{{ card.title }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ card.description }}</p>
                </div>
            </Link>
        </div>

        <!-- Upcoming Events Preview -->
        <div v-if="upcoming_events.length > 0">
            <h2 class="mb-3 text-lg font-semibold text-foreground">📅 Upcoming Events</h2>
            <div class="space-y-3">
                <div
                    v-for="event in upcoming_events"
                    :key="event.id"
                    class="card-premium flex items-center gap-3 p-4"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl text-xl"
                        :style="{ backgroundColor: event.colour + '20' }"
                        aria-hidden="true"
                    >
                        {{ event.emoji }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-foreground">{{ event.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ new Date(event.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long' }) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Moments Preview -->
        <div v-if="recent_moments.length > 0">
            <h2 class="mb-3 text-lg font-semibold text-foreground">📖 Recent Moments</h2>
            <div class="space-y-3">
                <div
                    v-for="moment in recent_moments"
                    :key="moment.id"
                    class="card-premium flex items-center gap-3 p-4"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-xl" aria-hidden="true">
                        {{ moment.is_favourite ? '⭐' : '📖' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-foreground">{{ moment.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ moment.mood ?? new Date(moment.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long' }) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Achievement Progress -->
        <div class="card-premium p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-foreground">🎯 Achievement Progress</h2>
                <Link href="/achievements" class="text-sm font-semibold text-primary hover:opacity-70 transition-opacity">
                    View all
                </Link>
            </div>
            <div class="mb-2 flex justify-between text-sm text-muted-foreground">
                <span>{{ achievement_progress.unlocked }} / {{ achievement_progress.total }} unlocked</span>
                <span>{{ achievement_progress.points }} pts</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full transition-all duration-700 gradient-primary"
                    :style="{ width: `${achievement_progress.percentage}%` }"
                    :aria-valuenow="achievement_progress.percentage"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    role="progressbar"
                />
            </div>
            <p class="mt-2 text-right text-sm font-semibold text-primary">{{ achievement_progress.percentage }}%</p>
        </div>

        <!-- Seasonal Questionnaires -->
        <div v-if="seasonal_questionnaires.length > 0">
            <h2 class="mb-3 text-lg font-semibold text-foreground">🎁 Seasonal Questionnaires</h2>
            <div class="space-y-3">
                <Link
                    v-for="q in seasonal_questionnaires"
                    :key="q.id"
                    :href="`/questionnaires/${q.slug}`"
                    class="card-premium card-hover flex items-center gap-3 p-4"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-xl" aria-hidden="true">
                        {{ q.emoji }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-foreground">{{ q.title }}</p>
                        <p class="line-clamp-1 text-sm text-muted-foreground">{{ q.description }}</p>
                    </div>
                    <span class="text-muted-foreground" aria-hidden="true">›</span>
                </Link>
            </div>
        </div>
    </div>
</template>
