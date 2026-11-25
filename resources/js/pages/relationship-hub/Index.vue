<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface Props {
    partner: { id: number; name: string; avatar: string | null } | null;
    stats: {
        current_streak: number;
        longest_streak: number;
        average_compatibility: number;
    };
    upcoming_events: Array<{
        id: number;
        title: string;
        emoji: string;
        date: string;
        colour: string;
    }>;
    recent_moments: Array<{
        id: number;
        title: string;
        mood: string | null;
        date: string;
        is_favourite: boolean;
    }>;
    achievement_progress: {
        total: number;
        unlocked: number;
        points: number;
        percentage: number;
    };
    seasonal_questionnaires: Array<{
        id: number;
        title: string;
        emoji: string;
        slug: string;
        description: string;
    }>;
    moments_count: number;
}

const props = defineProps<Props>();

const shortcuts = [
    {
        emoji: '🕰️',
        title: 'Timeline',
        description: 'Your story so far, in order',
        href: '/timeline',
    },
    {
        emoji: '❤️',
        title: 'Relationship insights',
        description: 'Your love story in numbers',
        href: '/insights',
    },
    {
        emoji: '📅',
        title: 'Upcoming events',
        description: `${props.upcoming_events.length} upcoming`,
        href: '/calendar',
    },
    {
        emoji: '📖',
        title: 'Recent moments',
        description: `${props.moments_count} captured`,
        href: '/moments',
    },
    {
        emoji: '🎯',
        title: 'Achievements',
        description: `${props.achievement_progress.unlocked}/${props.achievement_progress.total} unlocked`,
        href: '/achievements',
    },
    {
        emoji: '🎁',
        title: 'Seasonal events',
        description: 'Special questionnaires',
        href: '/questionnaires',
    },
    {
        emoji: '💌',
        title: 'Love notes',
        description: 'Sweet messages to your partner',
        href: '/love-notes',
    },
    {
        emoji: '🔥',
        title: 'Intimacy',
        description: 'Playful nights, just for two',
        href: '/intimacy',
    },
];
</script>

<template>
    <Head title="Relationship hub" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader eyebrow="Together" title="Relationship hub">
            <template #description>
                Everything about your relationship, in one place
                <span v-if="partner"> with {{ partner.name }}</span>
            </template>
        </PageHeader>

        <!-- Headline stats -->
        <section class="card-feature p-6" aria-label="Your relationship stats">
            <p class="eyebrow text-gold-soft">Average compatibility</p>
            <p
                class="mt-2 font-display text-6xl leading-none font-medium tabular-nums"
            >
                {{ stats.average_compatibility }}%
            </p>

            <div class="my-5 hairline-gold opacity-60" />

            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs text-white/80">Current streak</dt>
                    <dd class="mt-1 text-sm text-white/80">
                        <span
                            class="font-display text-3xl leading-none font-medium text-white tabular-nums"
                            >{{ stats.current_streak }}</span
                        >
                        days
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-white/80">Longest streak</dt>
                    <dd class="mt-1 text-sm text-white/80">
                        <span
                            class="font-display text-3xl leading-none font-medium text-white tabular-nums"
                            >{{ stats.longest_streak }}</span
                        >
                        days
                    </dd>
                </div>
            </dl>
        </section>

        <!-- Shortcuts -->
        <section class="space-y-3">
            <SectionHeader title="Explore" />

            <nav class="card-premium p-2" aria-label="Relationship hub">
                <Link
                    v-for="shortcut in shortcuts"
                    :key="shortcut.title"
                    :href="shortcut.href"
                    class="list-row"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                        >{{ shortcut.emoji }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-foreground">
                            {{ shortcut.title }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ shortcut.description }}
                        </p>
                    </div>
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                </Link>
            </nav>
        </section>

        <!-- Upcoming events -->
        <section v-if="upcoming_events.length > 0" class="space-y-3">
            <SectionHeader
                title="Upcoming events"
                action-label="View all"
                action-href="/calendar"
            />

            <div class="card-premium p-2">
                <div
                    v-for="event in upcoming_events"
                    :key="event.id"
                    class="flex items-center gap-3.5 p-3"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        :style="{
                            boxShadow: `inset 0 0 0 1.5px ${event.colour}`,
                        }"
                        aria-hidden="true"
                        >{{ event.emoji }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-foreground">
                            {{ event.title }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                new Date(event.date).toLocaleDateString(
                                    'en-GB',
                                    { day: 'numeric', month: 'long' },
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent moments -->
        <section v-if="recent_moments.length > 0" class="space-y-3">
            <SectionHeader
                title="Recent moments"
                action-label="View all"
                action-href="/moments"
            />

            <div class="card-premium p-2">
                <div
                    v-for="moment in recent_moments"
                    :key="moment.id"
                    class="flex items-center gap-3.5 p-3"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                        >{{ moment.is_favourite ? '⭐' : '📖' }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-foreground">
                            {{ moment.title }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                moment.mood ??
                                new Date(moment.date).toLocaleDateString(
                                    'en-GB',
                                    { day: 'numeric', month: 'long' },
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Achievement progress -->
        <section class="space-y-3">
            <SectionHeader
                title="Achievement progress"
                action-label="View all"
                action-href="/achievements"
            />

            <div class="space-y-4 card-premium p-5">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="font-display text-4xl leading-none font-medium text-foreground tabular-nums"
                        >
                            {{ achievement_progress.percentage }}%
                        </p>
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            {{ achievement_progress.unlocked }} /
                            {{ achievement_progress.total }} unlocked
                        </p>
                    </div>
                    <span class="badge-soft tabular-nums"
                        >{{ achievement_progress.points }} pts</span
                    >
                </div>
                <ProgressBar
                    :value="achievement_progress.percentage"
                    :max="100"
                />
            </div>
        </section>

        <!-- Seasonal questionnaires -->
        <section v-if="seasonal_questionnaires.length > 0" class="space-y-3">
            <SectionHeader title="Seasonal questionnaires" />

            <div class="card-premium p-2">
                <Link
                    v-for="q in seasonal_questionnaires"
                    :key="q.id"
                    :href="`/questionnaires/${q.slug}`"
                    class="list-row"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                        >{{ q.emoji }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-foreground">
                            {{ q.title }}
                        </p>
                        <p class="line-clamp-1 text-xs text-muted-foreground">
                            {{ q.description }}
                        </p>
                    </div>
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                </Link>
            </div>
        </section>
    </div>
</template>
