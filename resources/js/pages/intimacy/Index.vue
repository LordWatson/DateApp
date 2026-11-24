<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Clock } from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import SectionHeader from '@/components/SectionHeader.vue';
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
    { value: 'all', label: 'All' },
    { value: 'flirty', label: 'Flirty' },
    { value: 'spicy', label: 'Spicy' },
    { value: 'wild', label: 'Wild' },
] as const;

const activeFilter = ref<(typeof filters)[number]['value']>('all');

const filteredGames = computed(() =>
    activeFilter.value === 'all'
        ? props.games
        : props.games.filter((game) => game.intensity === activeFilter.value),
);
</script>

<template>
    <Head title="Intimacy" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <!-- Hero -->
        <section class="card-feature p-6">
            <p class="eyebrow text-gold-soft">The intimacy space</p>
            <h1 class="mt-2 text-4xl leading-tight font-medium">
                Just the two of you tonight
            </h1>
            <div class="my-5 hairline-gold opacity-60" />
            <p class="text-sm leading-relaxed text-white/80">
                Playful questionnaires and cheeky games designed to spark
                something unforgettable.
            </p>
        </section>

        <!-- Questionnaires -->
        <section class="space-y-3">
            <div class="space-y-1">
                <SectionHeader title="Tonight’s questionnaires" />
                <p class="text-sm text-muted-foreground">
                    Share your mood and let the sparks find you.
                </p>
            </div>

            <div class="card-premium p-2">
                <EmptyState
                    v-if="questionnaires.length === 0"
                    emoji="💫"
                    title="More coming soon"
                    description="New intimacy questionnaires drop regularly."
                />

                <template v-else>
                    <Link
                        v-for="questionnaire in questionnaires"
                        :key="questionnaire.id"
                        :href="`/questionnaires/${questionnaire.slug}`"
                        class="list-row"
                    >
                        <span
                            class="icon-tile h-12 w-12 text-2xl"
                            aria-hidden="true"
                            >{{ questionnaire.emoji ?? '💗' }}</span
                        >
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-foreground">
                                {{ questionnaire.title }}
                            </p>
                            <p
                                class="line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ questionnaire.description }}
                            </p>
                            <p
                                v-if="questionnaire.estimated_minutes"
                                class="mt-1 flex items-center gap-1 text-xs text-muted-foreground tabular-nums"
                            >
                                <Clock class="size-3.5" aria-hidden="true" />
                                {{ questionnaire.estimated_minutes }} min
                            </p>
                        </div>
                        <ChevronRight
                            class="size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </Link>
                </template>
            </div>
        </section>

        <!-- Games -->
        <section class="space-y-3">
            <div class="space-y-1">
                <SectionHeader title="Games for two" />
                <p class="text-sm text-muted-foreground">
                    Pick a vibe, let the game guide the night.
                </p>
            </div>

            <!-- Intensity filter -->
            <div
                class="flex gap-2 overflow-x-auto pb-1"
                role="group"
                aria-label="Filter by intensity"
            >
                <button
                    v-for="filter in filters"
                    :key="filter.value"
                    type="button"
                    class="chip"
                    :aria-pressed="activeFilter === filter.value"
                    @click="activeFilter = filter.value"
                >
                    {{ filter.label }}
                </button>
            </div>

            <div v-if="filteredGames.length === 0" class="card-premium">
                <EmptyState
                    emoji="🎈"
                    title="Nothing quite matches"
                    description="Try a different intensity above."
                />
            </div>

            <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <Link
                    v-for="game in filteredGames"
                    :key="game.id"
                    :href="`/intimacy/games/${game.slug}`"
                    class="flex card-hover flex-col gap-4 card-premium p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <span
                            class="icon-tile h-12 w-12 text-2xl"
                            aria-hidden="true"
                            >{{ game.emoji ?? '🎲' }}</span
                        >
                        <span class="badge-soft">
                            {{ game.intensity_label }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">
                            {{ game.title }}
                        </h3>
                        <p
                            v-if="game.tagline"
                            class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                        >
                            {{ game.tagline }}
                        </p>
                    </div>
                    <div
                        class="mt-auto flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>{{ game.category_label }}</span>
                        <span
                            v-if="game.estimated_minutes"
                            class="flex items-center gap-1 tabular-nums"
                        >
                            <Clock class="size-3.5" aria-hidden="true" />
                            {{ game.estimated_minutes }} min
                        </span>
                    </div>
                </Link>
            </div>
        </section>

        <!-- Safety note -->
        <div class="space-y-4">
            <div class="hairline-gold" />
            <p class="px-4 text-center text-xs text-muted-foreground">
                Always play with consent. Anything you share stays between you
                two.
            </p>
        </div>
    </div>
</template>
