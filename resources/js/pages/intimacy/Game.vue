<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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

const intensityBadgeClass = computed<string>(() => {
    switch (props.game.intensity) {
        case 'flirty':
            return 'bg-pink-100 text-pink-700';
        case 'spicy':
            return 'bg-orange-100 text-orange-700';
        case 'wild':
            return 'bg-rose-100 text-rose-700';
        default:
            return 'bg-muted text-foreground';
    }
});
</script>

<template>
    <Head :title="`${game.title} · Intimacy`" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <!-- Back -->
        <Link
            href="/intimacy"
            class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-muted-foreground hover:text-foreground"
        >
            ‹ Back to Intimacy
        </Link>

        <!-- Hero -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-pink-500 via-rose-500 to-fuchsia-600 p-6 text-white shadow-xl">
            <div class="absolute -right-6 -top-6 text-8xl opacity-20" aria-hidden="true">
                {{ game.emoji ?? '🎲' }}
            </div>
            <div class="relative">
                <span
                    class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold"
                    :class="intensityBadgeClass"
                >
                    {{ game.intensity_emoji }} {{ game.intensity_label }}
                </span>
                <h1 class="mt-3 text-3xl font-semibold leading-tight">{{ game.title }}</h1>
                <p v-if="game.tagline" class="mt-2 text-sm opacity-90">{{ game.tagline }}</p>
                <div class="mt-4 flex items-center gap-4 text-xs opacity-90">
                    <span>👥 {{ game.players }} players</span>
                    <span v-if="game.estimated_minutes">⏱ {{ game.estimated_minutes }} min</span>
                    <span>🏷️ {{ game.category_label }}</span>
                </div>
            </div>
        </div>

        <!-- Description -->
        <section class="card-premium p-6">
            <h2 class="text-lg font-semibold text-foreground">The vibe 💗</h2>
            <p class="mt-2 text-sm text-foreground/80">{{ game.description }}</p>
        </section>

        <!-- How to play -->
        <section class="card-premium p-6">
            <h2 class="text-lg font-semibold text-foreground">How to play 🎯</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-foreground/80">{{ game.how_to_play }}</p>
        </section>

        <!-- Prompts -->
        <section v-if="hasPrompts" class="space-y-4">
            <h2 class="text-lg font-semibold text-foreground">Your prompt cards 🃏</h2>

            <div class="card-premium relative min-h-[220px] overflow-hidden p-6 text-center">
                <transition name="fade" mode="out-in">
                    <div v-if="revealed" :key="currentIndex" class="flex min-h-[160px] flex-col items-center justify-center gap-3">
                        <div class="text-4xl" aria-hidden="true">💫</div>
                        <p class="text-lg font-semibold text-foreground">
                            {{ currentPrompt }}
                        </p>
                    </div>
                    <div v-else key="placeholder" class="flex min-h-[160px] flex-col items-center justify-center gap-3">
                        <div class="text-5xl" aria-hidden="true">🎴</div>
                        <p class="text-sm text-muted-foreground">
                            Tap below to draw your first card.
                        </p>
                    </div>
                </transition>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <button
                    v-if="!revealed"
                    type="button"
                    class="min-h-[56px] rounded-3xl bg-gradient-to-r from-pink-500 to-fuchsia-600 text-base font-semibold text-white shadow-xl transition-transform active:scale-[0.98]"
                    @click="reveal"
                >
                    Draw a Card 🎴
                </button>
                <button
                    v-else
                    type="button"
                    class="min-h-[56px] rounded-3xl bg-gradient-to-r from-pink-500 to-fuchsia-600 text-base font-semibold text-white shadow-xl transition-transform active:scale-[0.98]"
                    @click="nextPrompt"
                >
                    Draw Another 🔀
                </button>
            </div>

            <details class="card-premium p-4">
                <summary class="cursor-pointer text-sm font-semibold text-foreground">
                    Peek at all {{ game.prompts.length }} cards
                </summary>
                <ul class="mt-3 space-y-2 text-sm text-foreground/80">
                    <li
                        v-for="(prompt, index) in game.prompts"
                        :key="index"
                        class="rounded-2xl bg-pink-50/60 p-3"
                    >
                        <span class="mr-2 font-semibold text-pink-600">{{ index + 1 }}.</span>
                        {{ prompt }}
                    </li>
                </ul>
            </details>
        </section>

        <!-- Safety -->
        <div class="card-premium p-5 text-center text-sm text-muted-foreground">
            💗 Only play what feels good for both of you. You can stop, swap or skip any time.
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
