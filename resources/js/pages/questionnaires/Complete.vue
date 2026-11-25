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
    partner_name?: string | null;
    has_partner?: boolean;
    awaiting_plan: boolean;
}

const props = defineProps<Props>();

const completedTime = props.completed_at
    ? new Date(props.completed_at).toLocaleTimeString([], {
          hour: '2-digit',
          minute: '2-digit',
      })
    : null;

// ─── Generating screen ────────────────────────────────────────────────────
const generatingMessages: string[] = [
    'Mixing the perfect vibe…',
    'Sprinkling a little romance…',
    'Picking a theme just for you…',
    'Setting the mood…',
    'Warming up the good ideas…',
    'Almost ready — one last touch…',
];

const messageIndex = ref(0);
const currentMessage = computed(() => generatingMessages[messageIndex.value]);

let messageTimer: ReturnType<typeof setInterval> | null = null;
let pollTimer: ReturnType<typeof setInterval> | null = null;
let stopped = false;

const POLL_INTERVAL_MS = 2000;
// About 90 seconds. After that the plan is late, not "a moment" away.
const MAX_POLLS = 45;
let polls = 0;
const timedOut = ref(false);

const needsPartner = computed(
    () => !props.questionnaire.is_solo && props.has_partner === false,
);

const waitingMessage = computed(() =>
    needsPartner.value
        ? 'This questionnaire is planned from both of your answers. Connect your partner and we will plan your date once they have answered too.'
        : `We've let ${props.partner_name ?? 'your partner'} know. We'll plan your date the moment they finish.`,
);
const MESSAGE_INTERVAL_MS = 2500;

async function pollForPlan(): Promise<void> {
    if (stopped) {
        return;
    }

    if (++polls > MAX_POLLS) {
        stopped = true;
        stopTimers();
        timedOut.value = true;

        return;
    }

    try {
        const response = await fetch(
            `/questionnaires/${props.questionnaire.slug}/plan-status`,
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            return;
        }

        const data: { ready: boolean; plan_id: number | null } =
            await response.json();

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
        messageIndex.value =
            (messageIndex.value + 1) % generatingMessages.length;
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
    <Head
        :title="
            awaiting_plan ? 'Planning your date night…' : 'Tonight is planned'
        "
    />

    <div class="relative min-h-screen overflow-hidden">
        <FloatingHearts />

        <!-- Generating your Date Night ─────────────────────────────────── -->
        <div
            v-if="awaiting_plan"
            class="relative z-10 flex min-h-screen flex-col items-center justify-center px-6 pt-6 pb-28 text-center"
        >
            <div
                class="relative flex h-28 w-28 items-center justify-center"
                aria-hidden="true"
            >
                <span
                    class="absolute inset-0 animate-ping rounded-full bg-primary/10"
                ></span>
                <span
                    class="absolute inset-4 animate-pulse rounded-full bg-primary/15"
                ></span>
                <span class="relative icon-tile h-16 w-16 text-3xl">💞</span>
            </div>

            <p class="mt-8 eyebrow">{{ questionnaire.title }}</p>
            <h1
                class="mt-2 text-[1.75rem] leading-[1.15] font-semibold text-foreground"
            >
                We're planning your date night
            </h1>
            <p
                v-if="!timedOut"
                class="mt-3 text-sm leading-relaxed text-muted-foreground"
            >
                Hang tight — this only takes a moment.
            </p>
            <div v-else class="mt-4 w-full max-w-sm space-y-4" role="status">
                <p class="text-sm leading-relaxed text-muted-foreground">
                    This is taking longer than usual. Your answers are saved,
                    and we will send you a notification as soon as your date
                    night is ready.
                </p>
                <PrimaryButton full-width @click="router.visit('/dashboard')">
                    Back to home
                </PrimaryButton>
                <SecondaryButton
                    variant="ghost"
                    full-width
                    @click="router.visit('/date-night/history')"
                >
                    Your date nights
                </SecondaryButton>
            </div>

            <div
                v-if="!timedOut"
                class="mt-8 min-h-[3rem] w-full"
                aria-live="polite"
            >
                <transition
                    mode="out-in"
                    enter-active-class="transition duration-500 ease-out"
                    leave-active-class="transition duration-300 ease-in"
                    enter-from-class="opacity-0 translate-y-2"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <p
                        :key="currentMessage"
                        class="text-[15px] font-medium text-primary"
                    >
                        {{ currentMessage }}
                    </p>
                </transition>
            </div>

            <div v-if="!timedOut" class="mt-8 flex gap-2" aria-hidden="true">
                <span
                    class="size-1.5 animate-bounce rounded-full bg-primary"
                    style="animation-delay: 0ms"
                ></span>
                <span
                    class="size-1.5 animate-bounce rounded-full bg-primary"
                    style="animation-delay: 150ms"
                ></span>
                <span
                    class="size-1.5 animate-bounce rounded-full bg-primary"
                    style="animation-delay: 300ms"
                ></span>
            </div>
        </div>

        <!-- Waiting for partner ─────────────────────────────────────────── -->
        <div
            v-else
            class="relative z-10 flex min-h-screen flex-col justify-center space-y-8 px-4 pt-6 pb-28"
        >
            <SuccessAnimation
                title="Your answers are in"
                :message="waitingMessage"
                emoji="🎉"
            />

            <section
                :class="[
                    'card-feature grid divide-x divide-white/15 py-6 text-center',
                    completedTime ? 'grid-cols-3' : 'grid-cols-2',
                ]"
            >
                <div v-if="completedTime" class="space-y-2 px-3">
                    <p class="eyebrow text-gold-soft">Completed</p>
                    <p
                        class="font-display text-xl leading-none font-medium tabular-nums"
                    >
                        {{ completedTime }}
                    </p>
                </div>

                <div class="space-y-2 px-3">
                    <p class="eyebrow text-gold-soft">Streak</p>
                    <p
                        class="font-display text-xl leading-none font-medium tabular-nums"
                    >
                        {{ current_streak }}
                        <span class="font-sans text-xs text-white/80"
                            >days</span
                        >
                    </p>
                </div>

                <div class="space-y-2 px-3">
                    <p class="eyebrow text-gold-soft">Partner</p>
                    <p class="font-display text-xl leading-none font-medium">
                        {{ needsPartner ? 'Not connected' : 'Waiting' }}
                    </p>
                </div>
            </section>

            <div class="space-y-3">
                <PrimaryButton
                    v-if="needsPartner"
                    full-width
                    @click="router.visit('/partner')"
                >
                    Connect your partner
                </PrimaryButton>
                <PrimaryButton
                    v-else
                    full-width
                    @click="
                        router.visit(
                            `/questionnaires/${questionnaire.slug}/summary`,
                        )
                    "
                >
                    View summary
                </PrimaryButton>

                <SecondaryButton
                    variant="ghost"
                    full-width
                    @click="router.visit('/dashboard')"
                >
                    Return home
                </SecondaryButton>
            </div>
        </div>
    </div>
</template>
