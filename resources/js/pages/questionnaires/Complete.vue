<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import SuccessAnimation from '@/components/SuccessAnimation.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
    is_solo: boolean;
}

interface Props {
    questionnaire: QuestionnaireData;
    completed_at: string | null;
    current_streak: number;
    partner_completed: boolean;
    awaiting_plan: boolean;
}

const props = defineProps<Props>();

const completedTime = props.completed_at
    ? new Date(props.completed_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    : null;

// ─── Generating screen ────────────────────────────────────────────────────
const generatingMessages: string[] = [
    'Mixing the perfect vibe… 🎶',
    'Sprinkling a little romance… 💕',
    'Picking a theme just for you… ✨',
    'Setting the mood… 🕯️',
    'Warming up the good ideas… 🔥',
    'Almost ready — one last touch… 🌹',
];

const messageIndex = ref(0);
const currentMessage = computed(() => generatingMessages[messageIndex.value]);

let messageTimer: ReturnType<typeof setInterval> | null = null;
let pollTimer: ReturnType<typeof setInterval> | null = null;
let stopped = false;

const POLL_INTERVAL_MS = 2000;
const MESSAGE_INTERVAL_MS = 2500;

async function pollForPlan(): Promise<void> {
    if (stopped) {
        return;
    }

    try {
        const response = await fetch(`/questionnaires/${props.questionnaire.slug}/plan-status`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const data: { ready: boolean; plan_id: number | null } = await response.json();

        if (data.ready && data.plan_id) {
            stopped = true;
            stopTimers();
            router.visit(`/date-night/${data.plan_id}`);
        }
    } catch {
        // Silent — keep polling; a transient error shouldn't break the UX.
    }
}

function stopTimers(): void {
    if (messageTimer) {
        clearInterval(messageTimer);
        messageTimer = null;
    }
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

onMounted(() => {
    if (!props.awaiting_plan) {
        return;
    }

    messageTimer = setInterval(() => {
        messageIndex.value = (messageIndex.value + 1) % generatingMessages.length;
    }, MESSAGE_INTERVAL_MS);

    // Kick off an immediate check so a plan that's already been generated
    // (e.g. instant deterministic fallback) doesn't wait for the first tick.
    void pollForPlan();
    pollTimer = setInterval(() => void pollForPlan(), POLL_INTERVAL_MS);
});

onBeforeUnmount(() => {
    stopped = true;
    stopTimers();
});
</script>

<template>
    <Head :title="awaiting_plan ? 'Planning your Date Night…' : 'Tonight is Planned ❤️'" />

    <div class="relative min-h-screen overflow-hidden">
        <FloatingHearts />

        <!-- Generating your Date Night ─────────────────────────────────── -->
        <div
            v-if="awaiting_plan"
            class="relative z-10 flex min-h-screen flex-col items-center justify-center px-6 py-8 pb-24 text-center"
        >
            <div class="relative flex h-32 w-32 items-center justify-center">
                <span class="absolute inset-0 animate-ping rounded-full bg-primary/20"></span>
                <span class="absolute inset-3 animate-pulse rounded-full bg-primary/30"></span>
                <span class="relative text-6xl">💞</span>
            </div>

            <h1 class="mt-8 text-3xl font-semibold text-foreground">
                We're planning your Date Night
            </h1>
            <p class="mt-3 text-base text-muted-foreground">
                Hang tight — this only takes a moment.
            </p>

            <div class="mt-8 min-h-[3rem] w-full">
                <transition
                    mode="out-in"
                    enter-active-class="transition duration-500 ease-out"
                    leave-active-class="transition duration-300 ease-in"
                    enter-from-class="opacity-0 translate-y-2"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <p :key="currentMessage" class="text-lg font-semibold text-primary">
                        {{ currentMessage }}
                    </p>
                </transition>
            </div>

            <div class="mt-10 flex gap-2">
                <span class="h-2 w-2 animate-bounce rounded-full bg-primary" style="animation-delay: 0ms"></span>
                <span class="h-2 w-2 animate-bounce rounded-full bg-primary" style="animation-delay: 150ms"></span>
                <span class="h-2 w-2 animate-bounce rounded-full bg-primary" style="animation-delay: 300ms"></span>
            </div>
        </div>

        <!-- Waiting for partner ─────────────────────────────────────────── -->
        <div
            v-else
            class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-8 pb-24"
        >
            <SuccessAnimation
                title="Your answers are in ❤️"
                message="We'll craft your Date Night the moment your partner finishes."
                emoji="🎉"
            />

            <div class="mt-6 w-full space-y-3">
                <div v-if="completedTime" class="flex items-center justify-between rounded-2xl bg-muted/50 px-4 py-3">
                    <span class="text-sm text-muted-foreground">Completed at</span>
                    <span class="font-semibold text-foreground">{{ completedTime }}</span>
                </div>

                <div class="flex items-center justify-between rounded-2xl bg-muted/50 px-4 py-3">
                    <span class="text-sm text-muted-foreground">Current streak</span>
                    <span class="font-semibold text-foreground">🔥 {{ current_streak }} days</span>
                </div>

                <div class="flex items-center justify-between rounded-2xl bg-muted/50 px-4 py-3">
                    <span class="text-sm text-muted-foreground">Partner status</span>
                    <span class="font-semibold text-muted-foreground">⏳ Waiting</span>
                </div>
            </div>

            <div class="mt-8 w-full space-y-3">
                <PrimaryButton
                    full-width
                    @click="router.visit(`/questionnaires/${questionnaire.slug}/summary`)"
                >
                    View Summary
                </PrimaryButton>

                <SecondaryButton
                    full-width
                    @click="router.visit('/dashboard')"
                >
                    Return Home
                </SecondaryButton>
            </div>
        </div>
    </div>
</template>
