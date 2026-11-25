<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowRight, Clock, Sparkles, Users } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { cards as cardsRoute } from '@/routes/intimacy/games';

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
    /** The stored cards: played only when the AI deck cannot be written. */
    prompts: string[];
}

interface Props {
    game: IntimacyGame;
}

const props = defineProps<Props>();

const storageKey = `intimacy-deck:${props.game.slug}`;
// Ask for the next deck while this many cards are left, so a draw never waits.
const REFILL_AT = 3;
// A deck takes about four seconds to write; past this the stored cards are played.
const FETCH_TIMEOUT_MS = 12000;

// Cards still to draw (taken from the end). AI-written, or the game's stored cards as a fallback.
const deck = ref<string[]>([]);
const current = ref<string | null>(null);
const drawn = ref(0);
const loading = ref(false);
// False once the AI could not write a deck: the stored cards carry the rest of the visit.
const aiAvailable = ref(true);
// Cards dealt this visit, sent with each request so the next deck does not repeat them.
const seen: string[] = [];

const waiting = computed(() => loading.value && deck.value.length === 0);

function saveDeck(): void {
    try {
        localStorage.setItem(storageKey, JSON.stringify(deck.value));
    } catch {
        // No storage (private mode): the deck still works for this visit.
    }
}

/** The undrawn cards from an earlier visit, so a reload neither repeats cards nor asks the AI again. */
function loadDeck(): string[] {
    try {
        const saved: unknown = JSON.parse(
            localStorage.getItem(storageKey) ?? '[]',
        );

        if (
            Array.isArray(saved) &&
            saved.every((card) => typeof card === 'string')
        ) {
            return saved;
        }
    } catch {
        // No storage or bad data: start a fresh deck.
    }

    return [];
}

/** The game's stored cards, shuffled, never opening with the card on screen. */
function storedDeck(): string[] {
    const cards = props.game.prompts.filter((card) => card !== current.value);

    for (let i = cards.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [cards[i], cards[j]] = [cards[j], cards[i]];
    }

    return cards;
}

/** One request writes a whole deck: about three times faster than a request per card. */
async function fetchCards(): Promise<void> {
    if (loading.value) {
        return;
    }

    loading.value = true;
    let cards: string[] = [];

    try {
        const response = await fetch(cardsRoute.url(props.game.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ??
                        '',
                ),
            },
            body: JSON.stringify({ seen: [...seen, ...deck.value].slice(-40) }),
            signal: AbortSignal.timeout(FETCH_TIMEOUT_MS),
        });

        if (response.ok) {
            cards =
                ((await response.json()) as { cards?: string[] }).cards ?? [];
        }
    } catch {
        // Offline or too slow: handled below like any other failure.
    }

    aiAvailable.value = cards.length > 0;

    if (aiAvailable.value) {
        // The cards already waiting are drawn first.
        deck.value = [...cards.reverse(), ...deck.value];
    } else if (deck.value.length === 0) {
        deck.value = storedDeck();
    }

    saveDeck();
    loading.value = false;
}

function refill(): void {
    if (aiAvailable.value) {
        void fetchCards();
    } else if (deck.value.length === 0) {
        deck.value = storedDeck();
        saveDeck();
    }
}

function drawCard(): void {
    const card = deck.value.pop();

    if (card === undefined) {
        // Nothing could be loaded last time: ask again.
        aiAvailable.value = true;
        void fetchCards();

        return;
    }

    current.value = card;
    drawn.value++;
    seen.push(card);
    saveDeck();

    if (deck.value.length <= REFILL_AT) {
        refill();
    }
}

// The deck is written while the players read the rules.
onMounted(() => {
    deck.value = loadDeck();

    if (deck.value.length <= REFILL_AT) {
        void fetchCards();
    }
});

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
        <section class="space-y-3">
            <SectionHeader title="Your prompt cards" />

            <div class="card-feature p-6 text-center">
                <div aria-live="polite">
                    <transition name="fade" mode="out-in">
                        <div
                            v-if="current"
                            :key="drawn"
                            class="flex min-h-[180px] flex-col items-center justify-center gap-4"
                        >
                            <p class="eyebrow text-gold-soft tabular-nums">
                                Card {{ drawn }}
                            </p>
                            <p
                                class="font-display text-2xl leading-snug font-medium"
                            >
                                {{ current }}
                            </p>
                        </div>
                        <!-- The first deck is still being written -->
                        <div
                            v-else-if="waiting"
                            key="writing"
                            class="flex min-h-[180px] flex-col items-center justify-center gap-4"
                        >
                            <div
                                class="flex h-14 w-14 animate-pulse items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20"
                                aria-hidden="true"
                            >
                                <Sparkles class="size-6 text-gold-soft" />
                            </div>
                            <p class="text-sm text-white/80">
                                Writing a fresh deck for this game…
                            </p>
                            <div
                                class="w-full max-w-[14rem] animate-pulse space-y-2"
                                aria-hidden="true"
                            >
                                <div class="h-2.5 rounded-full bg-white/15" />
                                <div
                                    class="mx-auto h-2.5 w-2/3 rounded-full bg-white/15"
                                />
                            </div>
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
                                {{
                                    deck.length > 0
                                        ? 'Your deck is ready. Tap below to draw your first card.'
                                        : 'The cards could not be written just now.'
                                }}
                            </p>
                        </div>
                    </transition>
                </div>

                <div class="my-5 hairline-gold opacity-60" />

                <button
                    type="button"
                    :class="[
                        drawButtonClass,
                        waiting && 'cursor-wait opacity-70',
                    ]"
                    :disabled="waiting"
                    @click="drawCard"
                >
                    {{
                        waiting
                            ? current
                                ? 'Writing more cards…'
                                : 'Writing your cards…'
                            : deck.length === 0
                              ? 'Try again'
                              : current
                                ? 'Draw another'
                                : 'Draw a card'
                    }}
                    <span
                        class="flex size-9 items-center justify-center rounded-full text-white transition-transform gradient-primary group-hover:translate-x-0.5"
                    >
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </span>
                </button>
            </div>

            <p
                class="flex items-center justify-center gap-1.5 px-4 text-center text-xs text-muted-foreground"
            >
                <Sparkles
                    v-if="aiAvailable"
                    class="size-3.5 shrink-0 text-gold"
                    aria-hidden="true"
                />
                {{
                    aiAvailable
                        ? 'Every deck is written fresh for this game, so no two nights play the same.'
                        : 'Playing the classic deck for this game.'
                }}
            </p>
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
