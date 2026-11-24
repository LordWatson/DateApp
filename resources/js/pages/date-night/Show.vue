<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarPlus, Check, Heart, Printer, Star, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import InputError from '@/components/InputError.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

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
    is_liked: boolean;
    likes_count: number;
    created_at: string;
    questionnaire: { id: number; title: string; slug: string } | null;
    partner_one: Partner | null;
    partner_two: Partner | null;
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
            label: 'Meal suggestion',
            emoji: '🍽️',
            text: props.plan.meal_suggestion,
            quoted: false,
        },
        {
            label: 'Atmosphere',
            emoji: '🕯️',
            text: props.plan.atmosphere,
            quoted: false,
        },
        {
            label: 'Activity',
            emoji: '✨',
            text: props.plan.activity,
            quoted: false,
        },
        {
            label: 'Conversation prompt',
            emoji: '💬',
            text: props.plan.conversation_prompt,
            quoted: true,
        },
    ].filter((section) => section.text),
);

const actionChipClass =
    'chip focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';

function toggleFavourite(): void {
    router.post(
        `/date-night/${props.plan.id}/favourite`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isFavourite.value = !isFavourite.value;
            },
        },
    );
}

function exportPlan(): void {
    window.open(`/date-night/${props.plan.id}/export`, '_blank');
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
            <section class="card-feature px-6 py-8 text-center">
                <p class="eyebrow text-gold-soft">Your perfect evening</p>
                <h1 class="mt-3 text-4xl leading-tight font-medium">
                    {{ plan.theme }}
                </h1>
                <p class="mt-2 text-xs text-white/80">{{ generatedDate }}</p>

                <!-- Compatibility score (couple plans only) -->
                <template v-if="!plan.is_solo">
                    <div class="my-6 hairline-gold opacity-60" />
                    <p
                        class="font-display text-6xl leading-none font-medium tabular-nums"
                    >
                        {{ plan.compatibility_score }}%
                    </p>
                    <p class="mt-2 eyebrow text-gold-soft">Compatible</p>
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
                            class="size-4 fill-current text-gold-soft"
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

            <!-- The plan -->
            <section class="space-y-3">
                <div
                    v-for="section in planSections"
                    :key="section.label"
                    class="flex items-start gap-4 card-premium p-5"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                        >{{ section.emoji }}</span
                    >
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <p class="eyebrow">{{ section.label }}</p>
                        <p
                            v-if="section.quoted"
                            class="text-[15px] leading-relaxed text-foreground italic"
                        >
                            "{{ section.text }}"
                        </p>
                        <p
                            v-else
                            class="text-[15px] leading-relaxed text-foreground"
                        >
                            {{ section.text }}
                        </p>
                    </div>
                </div>

                <!-- Romantic challenge, highlighted -->
                <div
                    v-if="plan.romantic_challenge"
                    class="card-premium p-6 text-center"
                >
                    <span
                        class="mx-auto icon-tile h-12 w-12 text-2xl"
                        aria-hidden="true"
                        >🌹</span
                    >
                    <p class="mt-4 eyebrow">Tonight's romantic challenge</p>
                    <div class="mx-auto my-4 hairline-gold w-24" />
                    <p
                        class="font-display text-xl leading-snug font-medium text-foreground"
                    >
                        {{ plan.romantic_challenge }}
                    </p>
                </div>

                <!-- Local ideas (solo plans only) -->
                <div
                    v-if="plan.local_suggestions.length > 0"
                    class="space-y-4 card-premium p-5"
                >
                    <div class="flex items-start gap-4">
                        <span
                            class="icon-tile h-11 w-11 text-xl"
                            aria-hidden="true"
                            >📍</span
                        >
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <p class="eyebrow">
                                Local ideas<span v-if="plan.location_label">
                                    near {{ plan.location_label }}</span
                                >
                            </p>
                            <p class="text-xs text-muted-foreground">
                                A few starting points to explore — always
                                double-check opening times.
                            </p>
                        </div>
                    </div>
                    <ul class="space-y-2">
                        <li
                            v-for="(
                                suggestion, index
                            ) in plan.local_suggestions"
                            :key="index"
                            class="rounded-2xl bg-muted p-4"
                        >
                            <div
                                class="flex items-baseline justify-between gap-2"
                            >
                                <p class="text-sm font-medium text-foreground">
                                    {{ suggestion.name }}
                                </p>
                                <span
                                    v-if="suggestion.category"
                                    class="badge-soft shrink-0"
                                >
                                    {{ suggestion.category }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ suggestion.description }}
                            </p>
                        </li>
                    </ul>
                </div>
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
                                ? 'Saved to favourites'
                                : 'Save to favourites'
                        }}
                    </button>

                    <button
                        type="button"
                        :class="actionChipClass"
                        @click="exportPlan"
                    >
                        <Printer class="size-4" aria-hidden="true" />
                        Export plan
                    </button>
                </div>
            </section>
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
