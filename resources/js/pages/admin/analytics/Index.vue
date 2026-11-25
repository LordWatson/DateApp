<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps<{
    dau: Array<{ date: string; count: number }>;
    mau: Array<{ month: string; count: number }>;
    completions: Array<{ date: string; count: number }>;
    dropoff: Array<{ display_order: number; title: string; count: number }>;
    active_users: number;
    avg_compatibility: number;
    love_note_activity: Array<{ date: string; count: number }>;
    moment_activity: Array<{ date: string; count: number }>;
}>();
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">Analytics</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Application usage and engagement metrics. A user counts as
                active when they finished a questionnaire, saved a moment, sent
                a love note, unlocked an achievement or connected a partner.
                Logins are not tracked. Average compatibility covers couple
                plans only.
            </p>
        </div>

        <!-- Summary Cards -->
        <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Avg compatibility</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ avg_compatibility }}%
                </p>
            </div>
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Active users (30d)</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ active_users }}
                </p>
            </div>
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Completions (30d)</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ completions.reduce((s, d) => s + d.count, 0) }}
                </p>
            </div>
            <div class="card-premium rounded-2xl p-5">
                <p class="mb-1 eyebrow">Love notes (30d)</p>
                <p
                    class="font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ love_note_activity.reduce((s, d) => s + d.count, 0) }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Daily Users Chart (simple bar) -->
            <div class="card-premium rounded-2xl p-6">
                <h2 class="mb-4 text-lg font-semibold text-foreground">
                    Daily active users (30 days)
                </h2>
                <div class="flex h-32 items-end gap-1">
                    <div
                        v-for="day in dau"
                        :key="day.date"
                        :style="{
                            height: dau.length
                                ? `${(day.count / Math.max(...dau.map((d) => d.count), 1)) * 100}%`
                                : '0%',
                        }"
                        class="min-h-[2px] flex-1 rounded-t-sm bg-primary/70 transition-all"
                        :title="`${day.date}: ${day.count}`"
                    />
                </div>
                <p
                    v-if="!dau.length"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No data yet.
                </p>
            </div>

            <!-- Completions Chart -->
            <div class="card-premium rounded-2xl p-6">
                <h2 class="mb-4 text-lg font-semibold text-foreground">
                    Questionnaire completions (30 days)
                </h2>
                <div class="flex h-32 items-end gap-1">
                    <div
                        v-for="day in completions"
                        :key="day.date"
                        :style="{
                            height: completions.length
                                ? `${(day.count / Math.max(...completions.map((d) => d.count), 1)) * 100}%`
                                : '0%',
                        }"
                        class="min-h-[2px] flex-1 rounded-t-sm bg-primary/70 transition-all"
                        :title="`${day.date}: ${day.count}`"
                    />
                </div>
                <p
                    v-if="!completions.length"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No data yet.
                </p>
            </div>

            <!-- Drop-off by Question -->
            <div class="card-premium rounded-2xl p-6">
                <h2 class="mb-4 text-lg font-semibold text-foreground">
                    Answers by question
                </h2>
                <div class="space-y-2">
                    <div
                        v-for="q in dropoff.slice(0, 10)"
                        :key="q.display_order"
                        class="flex items-center gap-3"
                    >
                        <span
                            class="w-6 flex-shrink-0 text-xs text-muted-foreground"
                            >{{ q.display_order }}</span
                        >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs text-foreground">
                                {{ q.title }}
                            </p>
                            <div class="mt-1 h-1.5 rounded-full bg-muted">
                                <div
                                    :style="{
                                        width: dropoff.length
                                            ? `${(q.count / Math.max(...dropoff.map((d) => d.count), 1)) * 100}%`
                                            : '0%',
                                    }"
                                    class="h-full rounded-full bg-primary/70"
                                />
                            </div>
                        </div>
                        <span
                            class="flex-shrink-0 text-xs text-muted-foreground"
                            >{{ q.count }}</span
                        >
                    </div>
                    <p
                        v-if="!dropoff.length"
                        class="py-4 text-center text-sm text-muted-foreground"
                    >
                        No data yet.
                    </p>
                </div>
            </div>

            <!-- Monthly Active Users -->
            <div class="card-premium rounded-2xl p-6">
                <h2 class="mb-4 text-lg font-semibold text-foreground">
                    Monthly active users (12 months)
                </h2>
                <div class="flex h-32 items-end gap-1">
                    <div
                        v-for="month in mau"
                        :key="month.month"
                        :style="{
                            height: mau.length
                                ? `${(month.count / Math.max(...mau.map((d) => d.count), 1)) * 100}%`
                                : '0%',
                        }"
                        class="min-h-[2px] flex-1 rounded-t-sm bg-primary/70 transition-all"
                        :title="`${month.month}: ${month.count}`"
                    />
                </div>
                <p
                    v-if="!mau.length"
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No data yet.
                </p>
            </div>
        </div>
    </div>
</template>
