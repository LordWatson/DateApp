<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarPlus,
    Check,
    ChevronLeft,
    ChevronRight,
    Heart,
    Printer,
    Sparkles,
    Star,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import InputError from '@/components/InputError.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import {
    answers,
    compatibility,
    complete,
    exportMethod,
    regenerate,
    retryAi,
    toggleFavourite as toggleFavouriteRoute,
} from '@/routes/date-night';
import { index as momentsIndex, show as showMoment } from '@/routes/moments';
import { edit as editProfile } from '@/routes/profile';

defineOptions({ layout: MobileLayout });

interface Partner {
    id: number;
    name: string;
    avatar?: string;
}

interface LocalSuggestion {
    name: string;
    category: string;
    description: string;
}

interface Completion {
    name: string;
    rating: number;
    note: string | null;
    completed_on: string;
}

interface PlanMoment {
    id: number;
    title: string;
    mood: string | null;
    photo: string | null;
}

interface Plan {
    id: number;
    theme: string;
    theme_emoji: string | null;
    compatibility_score: number;
    summary: string;
    meal_suggestion: string | null;
    atmosphere: string | null;
    activity: string | null;
    conversation_prompt: string | null;
    romantic_challenge: string | null;
    is_solo: boolean;
    location_label: string | null;
    local_suggestions: LocalSuggestion[];
    is_favourite: boolean;
    ai_enhanced: boolean;
    fallback_used: boolean;
    is_liked: boolean;
    likes_count: number;
    created_at: string;
    questionnaire: { id: number; title: string; slug: string } | null;
    partner_one: Partner | null;
    partner_two: Partner | null;
    completion: { mine: Completion | null; partner: Completion | null };
    moments: PlanMoment[];
    last_steer: string | null;
    regenerations_left: number;
    can_regenerate: boolean;
    ai_available: boolean;
}

const props = defineProps<{ plan: Plan }>();

const isFavourite = ref(props.plan.is_favourite);
const isLiked = ref(props.plan.is_liked);
const likesCount = ref(props.plan.likes_count);

const generatedDate = new Date(props.plan.created_at).toLocaleDateString([], {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

const planSections = computed(() =>
    [
        {
            label: 'On the menu',
            emoji: '🍽️',
            text: props.plan.meal_suggestion,
            quoted: false,
        },
        {
            label: 'Set the scene',
            emoji: '🕯️',
            text: props.plan.atmosphere,
            quoted: false,
        },
        {
            label: 'The main event',
            emoji: '✨',
            text: props.plan.activity,
            quoted: false,
        },
        {
            label: 'Something to talk about',
            emoji: '💬',
            text: props.plan.conversation_prompt,
            quoted: true,
        },
    ].filter((section) => section.text),
);

// The evening is a swipeable deck: one card on screen at a time.
const deck = ref<HTMLElement | null>(null);
const activeStep = ref(0);

function onDeckScroll(): void {
    const el = deck.value;

    if (!el) {
        return;
    }

    const centre = el.scrollLeft + el.clientWidth / 2;
    const distances = Array.from(el.children).map((child) => {
        const card = child as HTMLElement;

        return Math.abs(card.offsetLeft + card.offsetWidth / 2 - centre);
    });

    activeStep.value = distances.indexOf(Math.min(...distances));
}

function goToStep(index: number): void {
    const card = deck.value?.children[index] as HTMLElement | undefined;

    card?.scrollIntoView({ block: 'nearest', inline: 'center' });
}

const challengeOpen = ref(false);

// Compatibility ring: counts up once when the page opens.
const RING_RADIUS = 52;
const RING_LENGTH = 2 * Math.PI * RING_RADIUS;
const shownScore = ref(0);

const matchLabel = computed(() => {
    const score = props.plan.compatibility_score;

    if (score >= 85) {
        return 'A dreamy match';
    }

    if (score >= 70) {
        return 'Beautifully in sync';
    }

    if (score >= 50) {
        return 'Opposites attract';
    }

    return 'Plenty to discover together';
});

onMounted(() => {
    const target = props.plan.compatibility_score ?? 0;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        shownScore.value = target;

        return;
    }

    const start = performance.now();
    const tick = (now: number): void => {
        const progress = Math.min((now - start) / 1200, 1);

        shownScore.value = Math.round(target * (1 - (1 - progress) ** 3));

        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    };

    requestAnimationFrame(tick);
});

const stepButtonClass =
    'flex size-10 items-center justify-center rounded-full border border-border bg-card text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none disabled:opacity-30';

const actionChipClass =
    'chip focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';

function toggleFavourite(): void {
    router.post(
        toggleFavouriteRoute(props.plan.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isFavourite.value = !isFavourite.value;
            },
        },
    );
}

function printPlan(): void {
    window.open(exportMethod(props.plan.id).url, '_blank');
}

const retryingAi = ref(false);

// The outcome comes back as a flash, which the layout shows as a toast.
function retryWithAi(): void {
    router.post(
        retryAi(props.plan.id).url,
        {},
        {
            preserveScroll: true,
            onStart: () => {
                retryingAi.value = true;
            },
            onFinish: () => {
                retryingAi.value = false;
            },
        },
    );
}

// "We did it": each person rates the night for themselves.
const showDoneModal = ref(false);

const doneForm = useForm({
    rating: props.plan.completion.mine?.rating ?? 0,
    note: props.plan.completion.mine?.note ?? '',
});

function submitDone(): void {
    doneForm.post(complete(props.plan.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            showDoneModal.value = false;
        },
    });
}

// "Not quite right?": one line of direction, and the AI rewrites the plan.
const steerOpen = ref(false);
const steerSuggestions = ['Cheaper', 'More active', 'Stay in', 'Less cooking'];
const steerForm = useForm({ steer: '' });

function submitSteer(): void {
    steerForm.post(regenerate(props.plan.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            steerForm.reset();
            steerOpen.value = false;
        },
    });
}

function toggleLike(): void {
    const wasLiked = isLiked.value;
    isLiked.value = !wasLiked;
    likesCount.value += wasLiked ? -1 : 1;

    router.post(
        `/date-night/${props.plan.id}/like`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                isLiked.value = wasLiked;
                likesCount.value += wasLiked ? 1 : -1;
            },
        },
    );
}

function getInitial(name: string): string {
    return name.charAt(0).toUpperCase();
}

const showCalendarModal = ref(false);
const calendarSaved = ref(false);

const today = computed(() => new Date().toISOString().slice(0, 10));

const calendarForm = useForm({
    date: today.value,
    time: '' as string,
    location: '' as string,
});

function openCalendarModal(): void {
    calendarSaved.value = false;
    calendarForm.clearErrors();
    showCalendarModal.value = true;
}

function closeCalendarModal(): void {
    showCalendarModal.value = false;
}

function submitCalendar(): void {
    calendarForm.post(`/date-night/${props.plan.id}/add-to-calendar`, {
        preserveScroll: true,
        onSuccess: () => {
            calendarSaved.value = true;
            showCalendarModal.value = false;
            calendarForm.reset('time', 'location');
        },
    });
}
</script>

<template>
    <Head title="Your date night" />

    <div class="relative min-h-screen overflow-hidden">
        <FloatingHearts />

        <div class="relative z-10 space-y-8 px-4 pt-6 pb-28">
            <!-- Hero -->
            <section
                class="card-feature px-6 py-8 text-center motion-safe:animate-in motion-safe:duration-500 motion-safe:zoom-in-95 motion-safe:fade-in"
            >
                <span
                    v-if="plan.theme_emoji"
                    class="mx-auto mb-4 flex size-16 items-center justify-center rounded-3xl bg-white/10 text-4xl ring-1 ring-white/20"
                    style="animation: success-pop 0.6s ease-out both"
                    aria-hidden="true"
                    >{{ plan.theme_emoji }}</span
                >
                <p class="eyebrow text-gold-soft">Your perfect evening</p>
                <h1 class="mt-3 text-4xl leading-tight font-medium">
                    {{ plan.theme }}
                </h1>
                <p class="mt-2 text-xs text-white/80">{{ generatedDate }}</p>

                <!-- Compatibility ring (couple plans only) -->
                <template v-if="!plan.is_solo">
                    <div class="my-6 hairline-gold opacity-60" />
                    <div
                        class="relative mx-auto size-36"
                        role="img"
                        :aria-label="`${plan.compatibility_score}% compatible. ${matchLabel}`"
                    >
                        <svg
                            viewBox="0 0 120 120"
                            class="size-full -rotate-90"
                            aria-hidden="true"
                        >
                            <circle
                                cx="60"
                                cy="60"
                                :r="RING_RADIUS"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="8"
                                class="text-white/15"
                            />
                            <circle
                                cx="60"
                                cy="60"
                                :r="RING_RADIUS"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="8"
                                stroke-linecap="round"
                                class="text-gold-soft"
                                :stroke-dasharray="RING_LENGTH"
                                :stroke-dashoffset="
                                    RING_LENGTH * (1 - shownScore / 100)
                                "
                            />
                        </svg>
                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center"
                            aria-hidden="true"
                        >
                            <span
                                class="font-display text-4xl leading-none font-medium tabular-nums"
                                >{{ shownScore }}%</span
                            >
                            <span class="mt-1 eyebrow text-gold-soft"
                                >Match</span
                            >
                        </div>
                    </div>
                    <p class="mt-4 font-display text-lg" aria-hidden="true">
                        {{ matchLabel }}
                    </p>
                </template>

                <!-- Partners -->
                <template v-if="plan.partner_one || plan.partner_two">
                    <div class="my-6 hairline-gold opacity-60" />
                    <div class="flex items-center justify-center gap-4">
                        <div
                            v-if="plan.partner_one"
                            class="flex flex-col items-center gap-1.5"
                        >
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 font-display text-lg ring-1 ring-white/20"
                                aria-hidden="true"
                            >
                                {{ getInitial(plan.partner_one.name) }}
                            </div>
                            <span class="text-xs text-white/80">{{
                                plan.partner_one.name
                            }}</span>
                        </div>
                        <Heart
                            v-if="!plan.is_solo && plan.partner_two"
                            class="size-4 fill-current text-gold-soft motion-safe:animate-pulse"
                            aria-hidden="true"
                        />
                        <div
                            v-if="!plan.is_solo && plan.partner_two"
                            class="flex flex-col items-center gap-1.5"
                        >
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 font-display text-lg ring-1 ring-white/20"
                                aria-hidden="true"
                            >
                                {{ getInitial(plan.partner_two.name) }}
                            </div>
                            <span class="text-xs text-white/80">{{
                                plan.partner_two.name
                            }}</span>
                        </div>
                    </div>
                </template>
            </section>

            <!-- Summary -->
            <p
                class="px-2 text-center font-display text-lg leading-relaxed text-foreground"
            >
                {{ plan.summary }}
            </p>

            <!-- The evening, one card at a time -->
            <section
                v-if="planSections.length > 0"
                class="space-y-4"
                aria-labelledby="evening-title"
            >
                <div class="flex items-baseline justify-between">
                    <h2 id="evening-title" class="eyebrow">
                        Your evening, step by step
                    </h2>
                    <p
                        v-if="planSections.length > 1"
                        class="text-xs text-muted-foreground tabular-nums"
                        aria-live="polite"
                    >
                        {{ activeStep + 1 }} of {{ planSections.length }}
                    </p>
                </div>

                <ol
                    ref="deck"
                    class="-mx-4 flex snap-x snap-mandatory [scrollbar-width:none] gap-3 overflow-x-auto scroll-smooth px-4 pb-2 [&::-webkit-scrollbar]:hidden"
                    @scroll.passive="onDeckScroll"
                >
                    <li
                        v-for="(section, index) in planSections"
                        :key="section.label"
                        class="flex shrink-0 basis-[86%] snap-center flex-col card-premium p-6 transition-all duration-300"
                        :class="
                            index === activeStep
                                ? 'opacity-100'
                                : 'scale-[0.97] opacity-60'
                        "
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="icon-tile h-14 w-14 text-3xl"
                                aria-hidden="true"
                                >{{ section.emoji }}</span
                            >
                            <span
                                class="font-display text-5xl leading-none text-primary/15 tabular-nums"
                                aria-hidden="true"
                                >{{ index + 1 }}</span
                            >
                        </div>
                        <h3
                            class="mt-5 font-display text-2xl font-medium text-foreground"
                        >
                            {{ section.label }}
                        </h3>
                        <div class="my-3 hairline-gold w-16" />
                        <p
                            class="text-[15px] leading-relaxed text-foreground"
                            :class="section.quoted && 'italic'"
                        >
                            <template v-if="section.quoted"
                                >"{{ section.text }}"</template
                            >
                            <template v-else>{{ section.text }}</template>
                        </p>
                    </li>
                </ol>

                <!-- Step controls -->
                <div
                    v-if="planSections.length > 1"
                    class="flex items-center justify-center gap-3"
                >
                    <button
                        type="button"
                        :class="stepButtonClass"
                        :disabled="activeStep === 0"
                        aria-label="Previous step"
                        @click="goToStep(activeStep - 1)"
                    >
                        <ChevronLeft class="size-4" aria-hidden="true" />
                    </button>
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="(section, index) in planSections"
                            :key="section.label"
                            type="button"
                            class="flex size-6 items-center justify-center rounded-full focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                            :aria-label="`Go to ${section.label}`"
                            :aria-current="
                                index === activeStep ? 'step' : undefined
                            "
                            @click="goToStep(index)"
                        >
                            <span
                                class="h-2 rounded-full transition-all duration-300"
                                :class="
                                    index === activeStep
                                        ? 'w-6 bg-primary'
                                        : 'w-2 bg-primary/25'
                                "
                            />
                        </button>
                    </div>
                    <button
                        type="button"
                        :class="stepButtonClass"
                        :disabled="activeStep === planSections.length - 1"
                        aria-label="Next step"
                        @click="goToStep(activeStep + 1)"
                    >
                        <ChevronRight class="size-4" aria-hidden="true" />
                    </button>
                </div>
            </section>

            <!-- Romantic challenge: sealed until opened -->
            <section
                v-if="plan.romantic_challenge"
                class="card-feature px-6 py-8 text-center"
            >
                <span
                    class="mx-auto flex size-14 items-center justify-center rounded-3xl bg-white/10 text-3xl ring-1 ring-white/20"
                    aria-hidden="true"
                    >{{ challengeOpen ? '🌹' : '💌' }}</span
                >
                <p class="mt-4 eyebrow text-gold-soft">
                    Tonight's romantic challenge
                </p>
                <div class="mx-auto my-4 hairline-gold w-24 opacity-60" />

                <div v-if="challengeOpen" role="status">
                    <p
                        class="font-display text-xl leading-snug font-medium motion-safe:animate-in motion-safe:duration-500 motion-safe:fade-in motion-safe:slide-in-from-bottom-2"
                    >
                        {{ plan.romantic_challenge }}
                    </p>
                </div>
                <template v-else>
                    <p class="text-sm text-white/80">
                        Sealed until you are ready.<template
                            v-if="!plan.is_solo"
                        >
                            Open it together.</template
                        >
                    </p>
                    <button
                        type="button"
                        class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-white px-6 text-sm font-medium text-primary shadow-lg transition-transform hover:scale-[1.03] focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary focus-visible:outline-none active:scale-95"
                        @click="challengeOpen = true"
                    >
                        <Sparkles class="size-4" aria-hidden="true" />
                        Reveal the challenge
                    </button>
                </template>
            </section>

            <!-- Local ideas (plans built around a place) -->
            <section
                v-if="plan.local_suggestions.length > 0"
                class="space-y-3"
                aria-labelledby="local-title"
            >
                <div>
                    <h2 id="local-title" class="eyebrow">
                        Local ideas<span v-if="plan.location_label">
                            near {{ plan.location_label }}</span
                        >
                    </h2>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Swipe for a few starting points. Always double-check
                        opening times.
                    </p>
                </div>
                <ul
                    class="-mx-4 flex snap-x snap-mandatory [scrollbar-width:none] gap-3 overflow-x-auto px-4 pb-2 [&::-webkit-scrollbar]:hidden"
                >
                    <li
                        v-for="(suggestion, index) in plan.local_suggestions"
                        :key="index"
                        class="flex shrink-0 basis-[72%] snap-center flex-col gap-2 card-premium p-5"
                    >
                        <span
                            v-if="suggestion.category"
                            class="badge-soft self-start"
                        >
                            {{ suggestion.category }}
                        </span>
                        <p class="font-display text-lg text-foreground">
                            {{ suggestion.name }}
                        </p>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ suggestion.description }}
                        </p>
                    </li>
                </ul>
            </section>

            <!-- How you matched (couple plans only) -->
            <section v-if="!plan.is_solo" class="space-y-3">
                <p class="eyebrow">How you matched</p>
                <div class="card-premium p-2">
                    <Link :href="compatibility(plan.id).url" class="list-row">
                        <span class="icon-tile h-11 w-11" aria-hidden="true">
                            <Heart class="size-5 text-primary" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-foreground">
                                Where you matched
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                Your score and what you agreed on
                            </p>
                        </div>
                        <ChevronRight
                            class="size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </Link>
                    <Link :href="answers(plan.id).url" class="list-row">
                        <span class="icon-tile h-11 w-11" aria-hidden="true">
                            <Check class="size-5 text-primary" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-foreground">
                                Compare your answers
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                Every question, side by side
                            </p>
                        </div>
                        <ChevronRight
                            class="size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </section>

            <!-- Written without AI: offer another go -->
            <section
                v-if="plan.fallback_used"
                class="space-y-3 card-premium p-5"
            >
                <div class="space-y-1">
                    <p class="font-medium text-foreground">
                        This plan uses our standard suggestions
                    </p>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        The AI that personalises plans was not available when
                        this one was made. You can ask it again. AI is only used
                        once everyone whose answers are in the plan has turned
                        it on in
                        <Link
                            :href="editProfile()"
                            class="font-medium text-primary underline underline-offset-4"
                            >Settings</Link
                        >.
                    </p>
                </div>
                <SecondaryButton
                    variant="outline"
                    full-width
                    :loading="retryingAi"
                    @click="retryWithAi"
                >
                    <Sparkles class="size-4" aria-hidden="true" />
                    Try again with AI
                </SecondaryButton>
            </section>

            <!-- Not quite right? One line of direction -->
            <section
                v-if="plan.can_regenerate || plan.last_steer"
                class="space-y-3 card-premium p-5"
            >
                <div class="space-y-1">
                    <p class="font-medium text-foreground">Not quite right?</p>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        <template v-if="plan.last_steer">
                            Last change asked for: "{{ plan.last_steer }}".
                        </template>
                        <template v-if="plan.can_regenerate">
                            Tell us what to change and we will rewrite the plan
                            ({{ plan.regenerations_left }} left).
                        </template>
                        <template v-else-if="plan.regenerations_left === 0">
                            This plan has used all of its changes.
                        </template>
                    </p>
                </div>

                <template v-if="plan.can_regenerate">
                    <SecondaryButton
                        v-if="!steerOpen"
                        variant="outline"
                        full-width
                        @click="steerOpen = true"
                    >
                        <Sparkles class="size-4" aria-hidden="true" />
                        Change this plan
                    </SecondaryButton>

                    <form
                        v-else
                        class="space-y-3"
                        @submit.prevent="submitSteer"
                    >
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="suggestion in steerSuggestions"
                                :key="suggestion"
                                type="button"
                                class="chip"
                                :aria-pressed="steerForm.steer === suggestion"
                                @click="steerForm.steer = suggestion"
                            >
                                {{ suggestion }}
                            </button>
                        </div>
                        <div>
                            <label for="plan-steer" class="sr-only"
                                >What should change?</label
                            >
                            <input
                                id="plan-steer"
                                v-model="steerForm.steer"
                                type="text"
                                maxlength="200"
                                required
                                placeholder="e.g. somewhere quieter, vegetarian…"
                                class="input-premium"
                            />
                            <InputError
                                :message="steerForm.errors.steer"
                                class="mt-1"
                            />
                        </div>
                        <PrimaryButton
                            type="submit"
                            full-width
                            :loading="steerForm.processing"
                            :disabled="steerForm.steer.trim().length < 3"
                        >
                            Rewrite the plan
                        </PrimaryButton>
                        <p
                            v-if="steerForm.processing"
                            class="text-center text-xs text-muted-foreground"
                            role="status"
                        >
                            This can take up to a minute.
                        </p>
                    </form>
                </template>
            </section>

            <!-- We did it -->
            <section class="space-y-3 card-premium p-5">
                <template
                    v-if="plan.completion.mine || plan.completion.partner"
                >
                    <p class="eyebrow">You did it</p>
                    <ul class="space-y-2">
                        <template
                            v-for="entry in [
                                plan.completion.mine,
                                plan.completion.partner,
                            ]"
                            :key="entry?.name"
                        >
                            <li v-if="entry" class="space-y-0.5">
                                <p
                                    class="flex items-center gap-2 text-sm font-medium text-foreground"
                                >
                                    <span class="min-w-0 truncate">{{
                                        entry === plan.completion.mine
                                            ? 'You'
                                            : entry.name
                                    }}</span>
                                    <span
                                        class="flex shrink-0 text-gold"
                                        role="img"
                                        :aria-label="`${entry.rating} out of 5`"
                                    >
                                        <Star
                                            v-for="n in 5"
                                            :key="n"
                                            class="size-4"
                                            :class="
                                                n <= entry.rating
                                                    ? 'fill-current'
                                                    : 'opacity-30'
                                            "
                                            aria-hidden="true"
                                        />
                                    </span>
                                </p>
                                <p
                                    v-if="entry.note"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ entry.note }}
                                </p>
                            </li>
                        </template>
                    </ul>

                    <ul v-if="plan.moments.length > 0" class="space-y-1">
                        <li v-for="moment in plan.moments" :key="moment.id">
                            <Link
                                :href="showMoment(moment.id).url"
                                class="list-row"
                            >
                                <img
                                    v-if="moment.photo"
                                    :src="moment.photo"
                                    alt=""
                                    class="size-11 shrink-0 rounded-xl object-cover"
                                />
                                <span
                                    v-else
                                    class="icon-tile h-11 w-11 text-xl"
                                    aria-hidden="true"
                                    >{{ moment.mood || '📸' }}</span
                                >
                                <span
                                    class="min-w-0 flex-1 truncate font-medium text-foreground"
                                    >{{ moment.title }}</span
                                >
                                <ChevronRight
                                    class="size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </Link>
                        </li>
                    </ul>

                    <div class="flex flex-col gap-2">
                        <PrimaryButton
                            full-width
                            @click="
                                router.visit(
                                    momentsIndex({
                                        query: { plan: plan.id },
                                    }).url,
                                )
                            "
                        >
                            Capture a moment from it
                        </PrimaryButton>
                        <SecondaryButton
                            variant="ghost"
                            full-width
                            @click="showDoneModal = true"
                        >
                            {{
                                plan.completion.mine
                                    ? 'Change my rating'
                                    : 'Add my rating'
                            }}
                        </SecondaryButton>
                    </div>
                </template>

                <template v-else>
                    <div class="space-y-1">
                        <p class="font-medium text-foreground">
                            Done this date night?
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Rate how it went and keep a memory of it.
                        </p>
                    </div>
                    <SecondaryButton
                        variant="outline"
                        full-width
                        @click="showDoneModal = true"
                    >
                        <Check class="size-4" aria-hidden="true" />
                        We did it
                    </SecondaryButton>
                </template>
            </section>

            <!-- Actions -->
            <section class="space-y-4">
                <PrimaryButton full-width @click="openCalendarModal">
                    <CalendarPlus class="size-4" aria-hidden="true" />
                    Add to calendar
                </PrimaryButton>

                <p
                    v-if="calendarSaved"
                    class="flex items-center justify-center gap-1.5 text-xs font-medium text-success"
                    role="status"
                >
                    <Check class="size-3.5" aria-hidden="true" />
                    Saved to your calendar
                </p>

                <div class="flex flex-wrap justify-center gap-2">
                    <button
                        type="button"
                        :class="actionChipClass"
                        :aria-pressed="isLiked"
                        :aria-label="
                            isLiked
                                ? 'Unlike this date night'
                                : 'Like this date night'
                        "
                        @click="toggleLike"
                    >
                        <Heart
                            :class="['size-4', isLiked && 'fill-current']"
                            aria-hidden="true"
                        />
                        <span>{{
                            isLiked ? 'You love this' : 'Love this date night'
                        }}</span>
                        <span v-if="likesCount > 0" class="tabular-nums">{{
                            likesCount
                        }}</span>
                    </button>

                    <button
                        type="button"
                        :class="actionChipClass"
                        :aria-pressed="isFavourite"
                        @click="toggleFavourite"
                    >
                        <Star
                            :class="['size-4', isFavourite && 'fill-current']"
                            aria-hidden="true"
                        />
                        {{
                            isFavourite
                                ? 'In your favourites'
                                : 'Save to my favourites'
                        }}
                    </button>

                    <button
                        type="button"
                        :class="actionChipClass"
                        @click="printPlan"
                    >
                        <Printer class="size-4" aria-hidden="true" />
                        Print
                    </button>
                </div>
            </section>
        </div>

        <!-- We did it modal -->
        <div
            v-if="showDoneModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="done-title"
            @click.self="showDoneModal = false"
        >
            <div
                class="w-full max-w-md card-premium rounded-b-none p-6 sm:rounded-3xl"
            >
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2
                            id="done-title"
                            class="text-xl font-semibold text-foreground"
                        >
                            How was it?
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Your rating is yours;
                            <template v-if="!plan.is_solo"
                                >your partner adds their own.</template
                            >
                            <template v-else>you can change it later.</template>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="-mt-1 -mr-2 flex size-10 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        aria-label="Close"
                        @click="showDoneModal = false"
                    >
                        <X class="size-5" aria-hidden="true" />
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitDone">
                    <div role="group" aria-labelledby="done-rating-label">
                        <p
                            id="done-rating-label"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Rating
                        </p>
                        <div class="flex gap-1">
                            <button
                                v-for="n in 5"
                                :key="n"
                                type="button"
                                class="flex size-11 items-center justify-center rounded-full text-gold transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                                :aria-label="`${n} out of 5`"
                                :aria-pressed="doneForm.rating === n"
                                @click="doneForm.rating = n"
                            >
                                <Star
                                    class="size-7"
                                    :class="
                                        n <= doneForm.rating
                                            ? 'fill-current'
                                            : 'opacity-30'
                                    "
                                    aria-hidden="true"
                                />
                            </button>
                        </div>
                        <InputError
                            :message="doneForm.errors.rating"
                            class="mt-1"
                        />
                    </div>

                    <div>
                        <label
                            for="done-note"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            A line about it (optional)
                        </label>
                        <textarea
                            id="done-note"
                            v-model="doneForm.note"
                            rows="3"
                            maxlength="500"
                            class="input-premium"
                        />
                        <InputError
                            :message="doneForm.errors.note"
                            class="mt-1"
                        />
                    </div>

                    <PrimaryButton
                        type="submit"
                        full-width
                        :loading="doneForm.processing"
                        :disabled="doneForm.rating < 1"
                    >
                        Save
                    </PrimaryButton>
                </form>
            </div>
        </div>

        <!-- Add to calendar modal -->
        <div
            v-if="showCalendarModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="add-to-calendar-title"
            @click.self="closeCalendarModal"
        >
            <div
                class="w-full max-w-md card-premium rounded-b-none p-6 sm:rounded-3xl"
            >
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2
                            id="add-to-calendar-title"
                            class="text-xl font-semibold text-foreground"
                        >
                            Add to calendar
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            When are you and your partner planning this evening?
                        </p>
                    </div>
                    <button
                        type="button"
                        class="-mt-1 -mr-2 flex size-10 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        aria-label="Close"
                        @click="closeCalendarModal"
                    >
                        <X class="size-5" aria-hidden="true" />
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitCalendar">
                    <div>
                        <label
                            for="calendar-date"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Date
                        </label>
                        <input
                            id="calendar-date"
                            v-model="calendarForm.date"
                            type="date"
                            :min="today"
                            required
                            class="input-premium"
                        />
                        <InputError
                            :message="calendarForm.errors.date"
                            class="mt-1"
                        />
                    </div>

                    <div>
                        <label
                            for="calendar-time"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Time (optional)
                        </label>
                        <input
                            id="calendar-time"
                            v-model="calendarForm.time"
                            type="time"
                            class="input-premium"
                        />
                        <InputError
                            :message="calendarForm.errors.time"
                            class="mt-1"
                        />
                    </div>

                    <div>
                        <label
                            for="calendar-location"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Location (optional)
                        </label>
                        <input
                            id="calendar-location"
                            v-model="calendarForm.location"
                            type="text"
                            maxlength="255"
                            placeholder="e.g. Home, our favourite bistro…"
                            class="input-premium"
                        />
                        <InputError
                            :message="calendarForm.errors.location"
                            class="mt-1"
                        />
                    </div>

                    <div class="flex flex-col gap-3 pt-2">
                        <PrimaryButton
                            type="submit"
                            full-width
                            :loading="calendarForm.processing"
                        >
                            Save to calendar
                        </PrimaryButton>
                        <SecondaryButton
                            variant="ghost"
                            full-width
                            @click="closeCalendarModal"
                        >
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
