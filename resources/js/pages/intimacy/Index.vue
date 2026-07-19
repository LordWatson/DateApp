<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface IntimacyQuestionnaire {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    emoji: string | null;
    estimated_minutes: number | null;
}

interface IntimacyGame {
    id: number;
    title: string;
    slug: string;
    emoji: string | null;
    tagline: string | null;
    intensity: string;
    intensity_label: string;
    intensity_emoji: string;
    category: string;
    category_label: string;
    estimated_minutes: number | null;
    players: number;
}

interface Props {
    questionnaires: IntimacyQuestionnaire[];
    games: IntimacyGame[];
}

const props = defineProps<Props>();

const filters = [
    { value: 'all', label: 'All', emoji: '✨' },
    { value: 'flirty', label: 'Flirty', emoji: '💋' },
    { value: 'spicy', label: 'Spicy', emoji: '🌶️' },
    { value: 'wild', label: 'Wild', emoji: '🔥' },
] as const;

const activeFilter = ref<(typeof filters)[number]['value']>('all');

const filteredGames = computed(() =>
    activeFilter.value === 'all'
        ? props.games
        : props.games.filter((game) => game.intensity === activeFilter.value),
);

const intensityBadgeClass = (intensity: string): string => {
    switch (intensity) {
        case 'flirty':
            return 'bg-pink-100 text-pink-700';
        case 'spicy':
            return 'bg-orange-100 text-orange-700';
        case 'wild':
            return 'bg-rose-100 text-rose-700';
        default:
            return 'bg-muted text-foreground';
    }
};
</script>

<template>
    <Head title="Intimacy" />

    <div class="space-y-8 px-4 py-6 pb-24">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-rose-500 via-pink-500 to-fuchsia-600 p-6 text-white shadow-xl">
            <div class="absolute -right-6 -top-6 text-8xl opacity-20" aria-hidden="true">🔥</div>
            <div class="relative">
                <p class="text-sm font-semibold uppercase tracking-wide opacity-90">The Intimacy Space</p>
                <h1 class="mt-2 text-3xl font-semibold leading-tight">
                    Just the two of you tonight 💕
                </h1>
                <p class="mt-2 text-sm opacity-90">
                    Playful questionnaires and cheeky games designed to spark something unforgettable.
                </p>
            </div>
        </div>

        <!-- Questionnaires -->
        <section>
            <div class="mb-3 flex items-end justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-foreground">Tonight’s Questionnaires 💞</h2>
                    <p class="text-sm text-muted-foreground">Share your mood and let the sparks find you.</p>
                </div>
            </div>

            <div v-if="questionnaires.length === 0" class="card-premium p-6 text-center">
                <div class="text-4xl" aria-hidden="true">💫</div>
                <p class="mt-2 font-semibold text-foreground">More coming soon</p>
                <p class="text-sm text-muted-foreground">New intimacy questionnaires drop regularly.</p>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="questionnaire in questionnaires"
                    :key="questionnaire.id"
                    :href="`/questionnaires/${questionnaire.slug}`"
                    class="card-premium group relative flex min-h-[6rem] items-center gap-4 overflow-hidden p-5 transition-transform hover:scale-[1.01]"
                >
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-100 to-fuchsia-100 text-2xl" aria-hidden="true">
                        {{ questionnaire.emoji ?? '💗' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-foreground">{{ questionnaire.title }}</p>
                        <p class="line-clamp-2 text-sm text-muted-foreground">
                            {{ questionnaire.description }}
                        </p>
                        <p v-if="questionnaire.estimated_minutes" class="mt-1 text-xs font-semibold text-pink-600">
                            ⏱ {{ questionnaire.estimated_minutes }} min
                        </p>
                    </div>
                    <span class="text-muted-foreground" aria-hidden="true">›</span>
                </Link>
            </div>
        </section>

        <!-- Games -->
        <section>
            <div class="mb-3 flex items-end justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-foreground">Games for Two 🎲</h2>
                    <p class="text-sm text-muted-foreground">Pick a vibe, let the game guide the night.</p>
                </div>
            </div>

            <!-- Intensity filter -->
            <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="filter in filters"
                    :key="filter.value"
                    type="button"
                    class="flex min-h-[44px] shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition-colors"
                    :class="
                        activeFilter === filter.value
                            ? 'bg-primary text-white shadow'
                            : 'card-premium text-foreground'
                    "
                    :aria-pressed="activeFilter === filter.value"
                    @click="activeFilter = filter.value"
                >
                    <span aria-hidden="true">{{ filter.emoji }}</span>
                    {{ filter.label }}
                </button>
            </div>

            <div v-if="filteredGames.length === 0" class="card-premium p-6 text-center">
                <div class="text-4xl" aria-hidden="true">🎈</div>
                <p class="mt-2 font-semibold text-foreground">Nothing quite matches</p>
                <p class="text-sm text-muted-foreground">Try a different intensity above.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Link
                    v-for="game in filteredGames"
                    :key="game.id"
                    :href="`/intimacy/games/${game.slug}`"
                    class="card-premium group flex flex-col gap-3 p-5 transition-transform hover:scale-[1.02]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-3xl" aria-hidden="true">{{ game.emoji ?? '🎲' }}</div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="intensityBadgeClass(game.intensity)"
                        >
                            {{ game.intensity_emoji }} {{ game.intensity_label }}
                        </span>
                    </div>
                    <div>
                        <p class="font-semibold text-foreground">{{ game.title }}</p>
                        <p v-if="game.tagline" class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                            {{ game.tagline }}
                        </p>
                    </div>
                    <div class="mt-auto flex items-center justify-between text-xs text-muted-foreground">
                        <span>{{ game.category_label }}</span>
                        <span v-if="game.estimated_minutes">⏱ {{ game.estimated_minutes }} min</span>
                    </div>
                </Link>
            </div>
        </section>

        <!-- Safety note -->
        <div class="card-premium p-5 text-center text-sm text-muted-foreground">
            💗 Always play with consent. Anything you share stays between you two.
        </div>
    </div>
</template>
