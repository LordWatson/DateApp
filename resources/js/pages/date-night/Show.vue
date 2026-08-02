<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
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

function toggleFavourite(): void {
    router.post(`/date-night/${props.plan.id}/favourite`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            isFavourite.value = !isFavourite.value;
        },
    });
}

function exportPlan(): void {
    window.open(`/date-night/${props.plan.id}/export`, '_blank');
}

function toggleLike(): void {
    const wasLiked = isLiked.value;
    isLiked.value = !wasLiked;
    likesCount.value += wasLiked ? -1 : 1;

    router.post(`/date-night/${props.plan.id}/like`, {}, {
        preserveScroll: true,
        onError: () => {
            isLiked.value = wasLiked;
            likesCount.value += wasLiked ? 1 : -1;
        },
    });
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
    <Head title="Your Date Night ❤️" />

    <div class="relative min-h-screen overflow-hidden">
        <FloatingHearts />

        <div class="relative z-10 px-4 py-6 pb-28">
            <!-- Hero Card -->
            <div class="mb-6 overflow-hidden rounded-3xl shadow-xl"
                 style="background: linear-gradient(135deg, #EC4899, #9333EA)">
                <div class="px-6 py-8 text-center text-white">
<!--                    <div class="mb-3 text-5xl">{{ plan.theme_emoji ?? '❤️' }}</div>-->
                    <div class="mb-3 text-5xl">❤️</div>
                    <h1 class="mb-1 text-2xl font-semibold">Your Perfect Evening</h1>
                    <p class="mb-4 text-sm opacity-85">{{ plan.theme }}</p>

                    <!-- Compatibility Score (couple plans only) -->
                    <div v-if="!plan.is_solo" class="inline-flex items-center gap-2 rounded-full bg-white/20 px-5 py-2 backdrop-blur-sm">
                        <span class="text-2xl font-bold">{{ plan.compatibility_score }}%</span>
                        <span class="text-sm opacity-90">Compatible ❤️</span>
                    </div>
                </div>

                <!-- Partner Avatars -->
                <div v-if="plan.partner_one || plan.partner_two"
                     class="flex items-center justify-center gap-4 border-t border-white/20 px-6 py-4">
                    <div v-if="plan.partner_one" class="flex flex-col items-center gap-1">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/30 text-sm font-bold text-white">
                            {{ getInitial(plan.partner_one.name) }}
                        </div>
                        <span class="text-xs text-white/80">{{ plan.partner_one.name }}</span>
                    </div>
                    <div v-if="!plan.is_solo && plan.partner_two" class="text-white/60">❤️</div>
                    <div v-if="!plan.is_solo && plan.partner_two" class="flex flex-col items-center gap-1">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/30 text-sm font-bold text-white">
                            {{ getInitial(plan.partner_two.name) }}
                        </div>
                        <span class="text-xs text-white/80">{{ plan.partner_two.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="card-premium mb-4 p-5">
                <p class="text-sm leading-relaxed text-foreground/80">{{ plan.summary }}</p>
            </div>

            <!-- Detail Cards -->
            <div class="mb-4 space-y-3">
                <div v-if="plan.meal_suggestion" class="card-premium card-hover flex items-start gap-4 p-5">
                    <span class="text-2xl" aria-hidden="true">🍽️</span>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Meal Suggestion</p>
                        <p class="text-sm font-medium text-foreground">{{ plan.meal_suggestion }}</p>
                    </div>
                </div>


                <div v-if="plan.atmosphere" class="card-premium card-hover flex items-start gap-4 p-5">
                    <span class="text-2xl" aria-hidden="true">🕯️</span>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Atmosphere</p>
                        <p class="text-sm font-medium text-foreground">{{ plan.atmosphere }}</p>
                    </div>
                </div>

                <div v-if="plan.activity" class="card-premium card-hover flex items-start gap-4 p-5">
                    <span class="text-2xl" aria-hidden="true">✨</span>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Activity</p>
                        <p class="text-sm font-medium text-foreground">{{ plan.activity }}</p>
                    </div>
                </div>

                <div v-if="plan.conversation_prompt" class="card-premium card-hover flex items-start gap-4 p-5">
                    <span class="text-2xl" aria-hidden="true">💬</span>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Conversation Prompt</p>
                        <p class="text-sm font-medium italic text-foreground">"{{ plan.conversation_prompt }}"</p>
                    </div>
                </div>

                <!-- Romantic Challenge — highlighted -->
                <div v-if="plan.romantic_challenge" class="card-premium p-5 bg-white">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-primary">🌹 Tonight's Romantic Challenge</p>
                    <p class="text-sm font-semibold text-secondary">{{ plan.romantic_challenge }}</p>
                </div>

                <!-- Local ideas (solo plans only) -->
                <div v-if="plan.is_solo && plan.local_suggestions.length > 0" class="card-premium p-5">
                    <div class="mb-3 flex items-start gap-3">
                        <span class="text-2xl" aria-hidden="true">📍</span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                Local ideas<span v-if="plan.location_label"> near {{ plan.location_label }}</span>
                            </p>
                            <p class="text-xs text-muted-foreground">A few starting points to explore — always double-check opening times.</p>
                        </div>
                    </div>
                    <ul class="space-y-3">
                        <li
                            v-for="(suggestion, index) in plan.local_suggestions"
                            :key="index"
                            class="rounded-2xl bg-primary/5 p-4"
                        >
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-sm font-semibold text-foreground">{{ suggestion.name }}</p>
                                <span
                                    v-if="suggestion.category"
                                    class="shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary"
                                >
                                    {{ suggestion.category }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">{{ suggestion.description }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Like -->
            <div class="mb-4 flex justify-center">
                <button
                    type="button"
                    class="inline-flex min-h-14 items-center gap-2 rounded-3xl bg-white px-6 py-3 text-sm font-semibold text-primary shadow-xl transition-transform duration-150 hover:scale-105 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    :aria-pressed="isLiked"
                    :aria-label="isLiked ? 'Unlike this date night' : 'Like this date night'"
                    @click="toggleLike"
                >
                    <span class="text-lg" aria-hidden="true">{{ isLiked ? '❤️' : '🤍' }}</span>
                    <span>{{ isLiked ? 'You love this' : 'Love this date night' }}</span>
                    <span v-if="likesCount > 0" class="rounded-full bg-primary/10 px-2 py-0.5 text-xs">{{ likesCount }}</span>
                </button>
            </div>

            <!-- Add to calendar -->
            <div class="mb-4 flex justify-center">
                <PrimaryButton size="md" @click="openCalendarModal">
                    📅 Add to Calendar
                </PrimaryButton>
            </div>

            <p v-if="calendarSaved" class="mb-4 text-center text-xs font-semibold text-success">
                💕 Saved to your calendar!
            </p>

            <!-- Generated time -->
            <p class="mb-6 text-center text-xs text-muted-foreground">Generated {{ generatedDate }}</p>

            <!-- Actions -->
<!--            <div class="space-y-3">
                <PrimaryButton full-width @click="exportPlan">
                    🖨️ Export Plan
                </PrimaryButton>

                <SecondaryButton full-width @click="toggleFavourite">
                    {{ isFavourite ? '💛 Saved to Favourites' : '🤍 Save to Favourites' }}
                </SecondaryButton>

                <SecondaryButton full-width @click="router.visit('/date-night/history')">
                    📖 View History
                </SecondaryButton>

                <SecondaryButton full-width @click="router.visit('/dashboard')">
                    Return Home
                </SecondaryButton>
            </div>-->
        </div>

        <!-- Add to Calendar Modal -->
        <div
            v-if="showCalendarModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 px-4 py-6 sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="add-to-calendar-title"
            @click.self="closeCalendarModal"
        >
            <div class="card-premium w-full max-w-md p-6">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="add-to-calendar-title" class="text-xl font-semibold text-foreground">
                            📅 Add to Calendar
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            When are you and your partner planning this evening?
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full p-2 text-muted-foreground hover:bg-primary/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        aria-label="Close"
                        @click="closeCalendarModal"
                    >
                        ✕
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitCalendar">
                    <div>
                        <label for="calendar-date" class="mb-1 block text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Date
                        </label>
                        <input
                            id="calendar-date"
                            v-model="calendarForm.date"
                            type="date"
                            :min="today"
                            required
                            class="w-full min-h-14 rounded-3xl border-2 border-border bg-background px-4 text-base text-foreground placeholder:text-muted-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors duration-200"
                        />
                        <InputError :message="calendarForm.errors.date" class="mt-1" />
                    </div>

                    <div>
                        <label for="calendar-time" class="mb-1 block text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Time (optional)
                        </label>
                        <input
                            id="calendar-time"
                            v-model="calendarForm.time"
                            type="time"
                            class="w-full min-h-14 rounded-3xl border-2 border-border bg-background px-4 text-base text-foreground placeholder:text-muted-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors duration-200"
                        />
                        <InputError :message="calendarForm.errors.time" class="mt-1" />
                    </div>

                    <div>
                        <label for="calendar-location" class="mb-1 block text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            Location (optional)
                        </label>
                        <input
                            id="calendar-location"
                            v-model="calendarForm.location"
                            type="text"
                            maxlength="255"
                            placeholder="e.g. Home, our favourite bistro…"
                            class="w-full min-h-14 rounded-3xl border-2 border-border bg-background px-4 text-base text-foreground placeholder:text-muted-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40 transition-colors duration-200"
                        />
                        <InputError :message="calendarForm.errors.location" class="mt-1" />
                    </div>

                    <div class="flex flex-col gap-3 pt-2">
                        <PrimaryButton type="submit" full-width :loading="calendarForm.processing">
                            💕 Save to Calendar
                        </PrimaryButton>
                        <SecondaryButton full-width @click="closeCalendarModal">
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
