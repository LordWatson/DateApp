<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { index } from '@/routes/insights';
import { regenerate as regenerateRoute } from '@/routes/weekly-reflection';

defineOptions({ layout: MobileLayout });

const regenerating = ref(false);

interface Reflection {
    id: number;
    week_start: string | null;
    week_end: string | null;
    headline: string;
    summary: string;
    highlights: string[];
    gentle_suggestion: string | null;
    encouragement: string | null;
    metrics: Record<string, unknown>;
    fallback_used: boolean;
    generated_at: string | null;
}

defineProps<{
    reflection: Reflection;
    history: Reflection[];
}>();

const formatRange = (start: string | null, end: string | null): string => {
    if (!start || !end) {
        return '';
    }

    const s = new Date(start);
    const e = new Date(end);
    const fmt: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };

    return `${s.toLocaleDateString(undefined, fmt)} – ${e.toLocaleDateString(undefined, fmt)}`;
};

const regenerate = (): void => {
    if (regenerating.value) {
        return;
    }

    router.post(regenerateRoute().url, {}, {
        preserveScroll: true,
        onStart: () => {
            regenerating.value = true;
        },
        onFinish: () => {
            regenerating.value = false;
        },
    });
};
</script>

<template>
    <Head title="This Week Together" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <!-- Header -->
        <div class="text-center">
            <div class="mb-2 text-5xl" aria-hidden="true">💞</div>
            <h1 class="text-2xl font-semibold text-foreground">This Week Together</h1>
            <p class="text-sm text-muted-foreground">A gentle look back at your week</p>
        </div>

        <!-- Main reflection card -->
        <div class="card-premium relative overflow-hidden p-6" :aria-busy="regenerating">
            <div class="absolute inset-0 bg-gradient-to-br from-pink-400 to-purple-500 opacity-5" aria-hidden="true" />

            <!-- Loading overlay while AI generates a new reflection -->
            <transition
                enter-active-class="transition-opacity duration-300"
                leave-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="regenerating"
                    class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 rounded-3xl bg-card/85 backdrop-blur-sm"
                    role="status"
                    aria-live="polite"
                >
                    <div class="relative flex h-16 w-16 items-center justify-center">
                        <span class="absolute inset-0 animate-ping rounded-full bg-primary/30" aria-hidden="true" />
                        <span class="relative text-3xl" aria-hidden="true">💞</span>
                    </div>
                    <div class="space-y-1 px-6 text-center">
                        <p class="text-base font-semibold text-foreground">Crafting your reflection…</p>
                        <p class="text-sm text-muted-foreground">This can take a few moments while we look back at your week.</p>
                    </div>
                    <div class="flex gap-1" aria-hidden="true">
                        <span class="h-2 w-2 animate-bounce rounded-full bg-primary [animation-delay:-0.3s]" />
                        <span class="h-2 w-2 animate-bounce rounded-full bg-primary [animation-delay:-0.15s]" />
                        <span class="h-2 w-2 animate-bounce rounded-full bg-primary" />
                    </div>
                    <span class="sr-only">Generating a new weekly reflection, please wait.</span>
                </div>
            </transition>
            <div class="relative space-y-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">
                        {{ formatRange(reflection.week_start, reflection.week_end) }}
                    </p>
                    <h2 class="mt-1 text-xl font-semibold text-foreground">
                        {{ reflection.headline }}
                    </h2>
                </div>

                <p class="text-base leading-relaxed text-foreground">
                    {{ reflection.summary }}
                </p>

                <div v-if="reflection.highlights.length > 0" class="space-y-2">
                    <p class="text-sm font-semibold text-foreground">Little wins</p>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, idx) in reflection.highlights"
                            :key="idx"
                            class="flex items-start gap-3 rounded-2xl bg-primary/5 p-3"
                        >
                            <span class="text-lg" aria-hidden="true">✨</span>
                            <span class="text-sm text-foreground">{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <div v-if="reflection.gentle_suggestion" class="rounded-2xl bg-secondary/10 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-secondary">A gentle idea</p>
                    <p class="mt-1 text-sm text-foreground">{{ reflection.gentle_suggestion }}</p>
                </div>

                <div v-if="reflection.encouragement" class="rounded-2xl bg-success/10 p-4 text-center">
                    <p class="text-sm italic text-foreground">“{{ reflection.encouragement }}”</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-3">
            <button
                type="button"
                class="flex min-h-14 items-center justify-center gap-2 rounded-3xl bg-gradient-to-r from-pink-500 to-purple-500 px-6 font-semibold text-white shadow-xl transition-transform hover:scale-[1.02] active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:scale-100"
                :disabled="regenerating"
                :aria-busy="regenerating"
                @click="regenerate"
            >
                <svg
                    v-if="regenerating"
                    class="h-5 w-5 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                </svg>
                <span>{{ regenerating ? 'Refreshing…' : 'Refresh reflection' }}</span>
            </button>
            <Link
                :href="index().url"
                class="min-h-14 rounded-3xl border border-border bg-card px-6 py-4 text-center font-semibold text-foreground shadow-sm"
            >
                Back to Insights
            </Link>
        </div>

        <!-- History -->
        <div v-if="history.length > 0" class="space-y-3">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Previous weeks</h3>
            <div
                v-for="entry in history"
                :key="entry.id"
                class="card-premium p-5"
            >
                <p class="text-xs uppercase tracking-wide text-muted-foreground">
                    {{ formatRange(entry.week_start, entry.week_end) }}
                </p>
                <p class="mt-1 font-semibold text-foreground">{{ entry.headline }}</p>
                <p class="mt-2 text-sm text-muted-foreground">{{ entry.summary }}</p>
            </div>
        </div>
    </div>
</template>
