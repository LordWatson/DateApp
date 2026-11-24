<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowRight, Clock, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface IntimacyGame {
    id: number;
    title: string;
    slug: string;
    emoji: string | null;
    tagline: string | null;
    description: string;
    how_to_play: string;
    players: number;
    estimated_minutes: number | null;
    intensity: string;
    intensity_label: string;
    intensity_emoji: string;
    category: string;
    category_label: string;
    prompts: string[];
}

interface Props {
    game: IntimacyGame;
}

const props = defineProps<Props>();

const currentIndex = ref(0);
const revealed = ref(false);

const currentPrompt = computed<string | null>(
    () => props.game.prompts[currentIndex.value] ?? null,
);

const hasPrompts = computed(() => props.game.prompts.length > 0);

const nextPrompt = (): void => {
    if (!hasPrompts.value) {
        return;
    }

    currentIndex.value = Math.floor(Math.random() * props.game.prompts.length);
    revealed.value = true;
};

const reveal = (): void => {
    revealed.value = true;
};

const drawButtonClass =
    'group flex min-h-12 w-full items-center justify-between rounded-full bg-white pr-2 pl-6 text-[15px] font-semibold tracking-tight text-primary transition-all duration-200 hover:-translate-y-px focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary focus-visible:outline-none active:scale-[0.98]';
</script>

<template>
    <Head :title="`${game.title} · Intimacy`" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <div class="space-y-4">
            <PageHeader
                :eyebrow="game.category_label"
                :title="game.title"
                :description="game.tagline ?? undefined"
                back-href="/intimacy"
                back-label="Back to intimacy"
            />

            <div
                class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground"
            >
                <span class="badge-soft">{{ game.intensity_label }}</span>
                <span class="flex items-center gap-1 tabular-nums">
                    <Users class="size-3.5" aria-hidden="true" />
                    {{ game.players }} players
                </span>
                <span
                    v-if="game.estimated_minutes"
                    class="flex items-center gap-1 tabular-nums"
                >
                    <Clock class="size-3.5" aria-hidden="true" />
                    {{ game.estimated_minutes }} min
                </span>
            </div>
        </div>

        <!-- Description and how to play -->
        <div class="space-y-5 card-premium p-6">
            <section class="space-y-2">
                <h2 class="font-sans eyebrow">The vibe</h2>
                <p class="text-[15px] leading-relaxed text-foreground">
                    {{ game.description }}
                </p>
            </section>

            <div class="hairline-gold" />

            <section class="space-y-2">
                <h2 class="font-sans eyebrow">How to play</h2>
                <p
                    class="text-[15px] leading-relaxed whitespace-pre-line text-foreground"
                >
                    {{ game.how_to_play }}
                </p>
            </section>
        </div>

        <!-- Prompts -->
        <section v-if="hasPrompts" class="space-y-3">
            <SectionHeader title="Your prompt cards" />

            <div class="card-feature p-6 text-center">
                <div aria-live="polite">
                    <transition name="fade" mode="out-in">
                        <div
                            v-if="revealed"
                            :key="currentIndex"
                            class="flex min-h-[180px] flex-col items-center justify-center gap-4"
                        >
                            <p class="eyebrow text-gold-soft tabular-nums">
                                Card {{ currentIndex + 1 }} of
                                {{ game.prompts.length }}
                            </p>
                            <p
                                class="font-display text-2xl leading-snug font-medium"
                            >
                                {{ currentPrompt }}
                            </p>
                        </div>
                        <div
                            v-else
                            key="placeholder"
                            class="flex min-h-[180px] flex-col items-center justify-center gap-4"
                        >
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-3xl ring-1 ring-white/20"
                                aria-hidden="true"
                            >
                                {{ game.emoji ?? '🎲' }}
                            </div>
                            <p class="text-sm text-white/80">
                                Tap below to draw your first card.
                            </p>
                        </div>
                    </transition>
                </div>

                <div class="my-5 hairline-gold opacity-60" />

                <button
                    v-if="!revealed"
                    type="button"
                    :class="drawButtonClass"
                    @click="reveal"
                >
                    Draw a card
                    <span
                        class="flex size-9 items-center justify-center rounded-full text-white transition-transform gradient-primary group-hover:translate-x-0.5"
                    >
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </span>
                </button>
                <button
                    v-else
                    type="button"
                    :class="drawButtonClass"
                    @click="nextPrompt"
                >
                    Draw another
                    <span
                        class="flex size-9 items-center justify-center rounded-full text-white transition-transform gradient-primary group-hover:translate-x-0.5"
                    >
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </span>
                </button>
            </div>

            <details class="card-premium p-5">
                <summary
                    class="cursor-pointer rounded-lg text-sm font-medium text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                >
                    Peek at all {{ game.prompts.length }} cards
                </summary>
                <ul class="mt-4 space-y-2">
                    <li
                        v-for="(prompt, index) in game.prompts"
                        :key="index"
                        class="flex gap-3 rounded-2xl bg-muted p-3 text-sm text-foreground"
                    >
                        <span
                            class="font-display font-medium text-primary tabular-nums"
                            >{{ index + 1 }}.</span
                        >
                        <span>{{ prompt }}</span>
                    </li>
                </ul>
            </details>
        </section>

        <!-- Safety -->
        <div class="space-y-4">
            <div class="hairline-gold" />
            <p class="px-4 text-center text-xs text-muted-foreground">
                Only play what feels good for both of you. You can stop, swap or
                skip any time.
            </p>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 200ms ease,
        transform 200ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
