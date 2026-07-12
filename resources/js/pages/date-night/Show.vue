<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface Partner {
    id: number;
    name: string;
    avatar?: string;
}

interface Plan {
    id: number;
    theme: string;
    theme_emoji: string | null;
    compatibility_score: number;
    summary: string;
    meal_suggestion: string | null;
    drink_suggestion: string | null;
    music_vibe: string | null;
    atmosphere: string | null;
    activity: string | null;
    conversation_prompt: string | null;
    romantic_challenge: string | null;
    is_favourite: boolean;
    created_at: string;
    questionnaire: { id: number; title: string; slug: string } | null;
    partner_one: Partner | null;
    partner_two: Partner | null;
}

const props = defineProps<{ plan: Plan }>();

const isFavourite = ref(props.plan.is_favourite);

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

function getInitial(name: string): string {
    return name.charAt(0).toUpperCase();
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
                    <div class="mb-3 text-5xl">{{ plan.theme_emoji ?? '❤️' }}</div>
                    <h1 class="mb-1 text-2xl font-semibold">Your Perfect Evening</h1>
                    <p class="mb-4 text-sm opacity-85">{{ plan.theme }}</p>

                    <!-- Compatibility Score -->
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/20 px-5 py-2 backdrop-blur-sm">
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
                    <div class="text-white/60">❤️</div>
                    <div v-if="plan.partner_two" class="flex flex-col items-center gap-1">
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

                <div v-if="plan.drink_suggestion" class="card-premium card-hover flex items-start gap-4 p-5">
                    <span class="text-2xl" aria-hidden="true">🥂</span>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Drink Suggestion</p>
                        <p class="text-sm font-medium text-foreground">{{ plan.drink_suggestion }}</p>
                    </div>
                </div>

                <div v-if="plan.music_vibe" class="card-premium card-hover flex items-start gap-4 p-5">
                    <span class="text-2xl" aria-hidden="true">🎵</span>
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Music Vibe</p>
                        <p class="text-sm font-medium text-foreground">{{ plan.music_vibe }}</p>
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
                <div v-if="plan.romantic_challenge" class="card-premium p-5 gradient-primary-soft">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-primary">🌹 Tonight's Romantic Challenge</p>
                    <p class="text-sm font-semibold text-foreground">{{ plan.romantic_challenge }}</p>
                </div>
            </div>

            <!-- Generated time -->
            <p class="mb-6 text-center text-xs text-muted-foreground">Generated {{ generatedDate }}</p>

            <!-- Actions -->
            <div class="space-y-3">
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
            </div>
        </div>
    </div>
</template>
