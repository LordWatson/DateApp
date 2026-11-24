<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import StatCard from '@/components/StatCard.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { index as weeklyReflectionIndex } from '@/routes/weekly-reflection';

defineOptions({ layout: MobileLayout });

const loadingReflection = ref(false);

const openWeeklyReflection = (): void => {
    if (loadingReflection.value) {
        return;
    }

    router.visit(weeklyReflectionIndex().url, {
        onStart: () => {
            loadingReflection.value = true;
        },
        onFinish: () => {
            loadingReflection.value = false;
        },
    });
};

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
</script>

<template>
    <Head title="Relationship insights" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Insights"
            title="Relationship insights"
            description="Your love story in numbers"
        />

        <!-- Headline stats -->
        <section
            class="card-feature p-6"
            aria-label="Compatibility and streaks"
        >
            <p class="eyebrow text-gold-soft">Average compatibility</p>
            <p
                class="mt-2 font-display text-6xl leading-none font-medium tabular-nums"
            >
                {{ insights.average_compatibility }}%
            </p>

            <div class="my-5 hairline-gold opacity-60" />

            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs text-white/80">Current streak</dt>
                    <dd class="mt-1 text-sm text-white/80">
                        <span
                            class="font-display text-3xl leading-none font-medium text-white tabular-nums"
                            >{{ insights.current_streak }}</span
                        >
                        days
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-white/80">Longest streak</dt>
                    <dd class="mt-1 text-sm text-white/80">
                        <span
                            class="font-display text-3xl leading-none font-medium text-white tabular-nums"
                            >{{ insights.longest_streak }}</span
                        >
                        days
                    </dd>
                </div>
            </dl>
        </section>

        <!-- Weekly reflection entry -->
        <button
            type="button"
            class="relative block w-full card-hover overflow-hidden card-premium p-4 text-left focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none disabled:pointer-events-none"
            :disabled="loadingReflection"
            :aria-busy="loadingReflection"
            @click="openWeeklyReflection"
        >
            <!-- Loading overlay while AI generates the reflection -->
            <transition
                enter-active-class="transition-opacity duration-300"
                leave-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="loadingReflection"
                    class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-3xl bg-card/90 backdrop-blur-sm"
                    role="status"
                    aria-live="polite"
                >
                    <p class="text-sm font-medium text-foreground">
                        Crafting your reflection…
                    </p>
                    <p class="px-6 text-center text-xs text-muted-foreground">
                        This can take a few moments while we look back at your
                        week.
                    </p>
                    <div class="flex gap-1" aria-hidden="true">
                        <span
                            class="size-1.5 animate-bounce rounded-full bg-primary [animation-delay:-0.3s]"
                        />
                        <span
                            class="size-1.5 animate-bounce rounded-full bg-primary [animation-delay:-0.15s]"
                        />
                        <span
                            class="size-1.5 animate-bounce rounded-full bg-primary"
                        />
                    </div>
                    <span class="sr-only"
                        >Generating your weekly reflection, please wait.</span
                    >
                </div>
            </transition>

            <div class="flex items-center gap-4">
                <div class="icon-tile h-12 w-12 text-2xl" aria-hidden="true">
                    💞
                </div>
                <div class="min-w-0 flex-1">
                    <p class="eyebrow">This week together</p>
                    <p
                        class="font-display text-lg leading-tight font-semibold text-foreground"
                    >
                        Your weekly reflection
                    </p>
                    <p class="text-sm text-muted-foreground">
                        A gentle, supportive look at your past week.
                    </p>
                </div>
                <ChevronRight
                    class="size-5 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
            </div>
        </button>

        <!-- Activity -->
        <section class="grid grid-cols-2 gap-3" aria-label="Your activity">
            <StatCard
                label="Questionnaires"
                :value="String(insights.total_questionnaires)"
            />
            <StatCard
                label="Plans"
                :value="String(insights.total_plans)"
                description="generated"
            />
            <StatCard label="Moments" :value="String(insights.moments_count)" />
            <StatCard
                label="Calendar"
                :value="String(insights.calendar_events_count)"
                description="events"
            />
        </section>

        <!-- Compatibility over time -->
        <section
            v-if="insights.compatibility_over_time.length > 0"
            class="space-y-3"
        >
            <SectionHeader title="Compatibility over time" />

            <div class="card-premium p-6 pt-9">
                <div
                    class="flex h-30 items-end gap-2"
                    role="img"
                    aria-label="Compatibility chart over time"
                >
                    <div
                        v-for="point in insights.compatibility_over_time"
                        :key="point.month"
                        class="group relative flex h-full flex-1 flex-col items-center justify-end"
                    >
                        <div
                            class="absolute -top-6 hidden text-xs font-semibold text-primary tabular-nums group-hover:block"
                        >
                            {{ point.score }}%
                        </div>
                        <div
                            class="w-full rounded-t-md bg-primary transition-all duration-500"
                            :style="{ height: `${point.score}%` }"
                        />
                        <div
                            class="mt-1.5 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ point.month.slice(5) }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Highlights -->
        <section class="space-y-3">
            <SectionHeader title="Highlights" />

            <div class="card-premium p-2">
                <div
                    v-if="insights.favourite_theme"
                    class="flex items-center gap-3.5 p-3"
                >
                    <span class="icon-tile h-11 w-11 text-xl" aria-hidden="true"
                        >🌙</span
                    >
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">
                            Favourite theme
                        </p>
                        <p class="font-medium text-foreground capitalize">
                            {{ insights.favourite_theme }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="insights.most_selected_atmosphere"
                    class="flex items-center gap-3.5 p-3"
                >
                    <span class="icon-tile h-11 w-11 text-xl" aria-hidden="true"
                        >✨</span
                    >
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">
                            Favourite atmosphere
                        </p>
                        <p class="font-medium text-foreground capitalize">
                            {{ insights.most_selected_atmosphere }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="insights.most_completed_month"
                    class="flex items-center gap-3.5 p-3"
                >
                    <span class="icon-tile h-11 w-11 text-xl" aria-hidden="true"
                        >📅</span
                    >
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">
                            Most active month
                        </p>
                        <p class="font-medium text-foreground">
                            {{ insights.most_completed_month }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5 p-3">
                    <span class="icon-tile h-11 w-11 text-xl" aria-hidden="true"
                        >💌</span
                    >
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Love notes</p>
                        <p class="font-medium text-foreground tabular-nums">
                            {{ insights.love_notes_sent }} sent ·
                            {{ insights.love_notes_received }} received
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Empty state -->
        <div v-if="insights.total_questionnaires === 0" class="card-premium">
            <EmptyState
                emoji="💕"
                title="Your story is just beginning"
                description="Complete questionnaires together to see your insights grow."
            />
        </div>
    </div>
</template>
