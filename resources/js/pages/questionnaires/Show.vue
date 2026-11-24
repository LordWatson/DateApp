<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Check,
    ChevronRight,
    Clock,
    RotateCcw,
    UserPlus,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import OptionCard from '@/components/OptionCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import StatCard from '@/components/StatCard.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    emoji: string | null;
    estimated_minutes: number | null;
    question_count: number;
    is_solo: boolean;
}

interface ModeData {
    selectable: boolean;
    current: 'solo' | 'couple' | null;
    has_partner: boolean;
    partner_name: string | null;
}

interface ResponseData {
    status: string;
    answered_count: number;
    resume_order: number;
}

interface SavedProfile {
    id: number;
    name: string;
    emoji: string | null;
    colour: string | null;
}

interface PastAttempt {
    id: number;
    completed_at: string | null;
}

interface Props {
    questionnaire: QuestionnaireData;
    response: ResponseData | null;
    partner_completed: boolean;
    mode: ModeData;
    current_streak: number;
    saved_profiles: SavedProfile[];
    past_attempts: PastAttempt[];
}

const props = withDefaults(defineProps<Props>(), {
    past_attempts: () => [],
});
const starting = ref(false);
const restarting = ref(false);
const showProfilePicker = ref(false);

// A waiting partner comes first; otherwise keep the mode of the attempt already under way.
const selectedMode = ref<'solo' | 'couple'>(
    props.mode.has_partner && props.partner_completed
        ? 'couple'
        : (props.mode.current ?? 'solo'),
);

const startPayload = computed(() =>
    props.mode.selectable ? { mode: selectedMode.value } : {},
);

const planningTogether = computed(() =>
    props.mode.selectable
        ? selectedMode.value === 'couple'
        : !props.questionnaire.is_solo,
);

const coupleDescription = computed(() =>
    props.partner_completed
        ? `${props.mode.partner_name} has already answered and is waiting for you.`
        : `You both answer, then we plan a date that works for you both.`,
);

function startFresh(): void {
    starting.value = true;
    router.post(
        `/questionnaires/${props.questionnaire.slug}/start`,
        startPayload.value,
        {
            onFinish: () => {
                starting.value = false;
            },
        },
    );
}

function restart(): void {
    restarting.value = true;
    router.post(
        `/questionnaires/${props.questionnaire.slug}/restart`,
        startPayload.value,
        {
            onFinish: () => {
                restarting.value = false;
            },
        },
    );
}

function viewPastAttempt(responseId: number): void {
    router.visit(
        `/questionnaires/${props.questionnaire.slug}/past-attempts/${responseId}`,
    );
}

function formatDate(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function resumeQuestionnaire(): void {
    // Starting again keeps the answers, saves a changed mode and returns to the first open question.
    if (props.mode.selectable) {
        startFresh();

        return;
    }

    if (props.response) {
        router.visit(
            `/questionnaires/${props.questionnaire.slug}/question/${props.response.resume_order}`,
        );
    }
}

function applyProfile(profileId: number): void {
    router.post(
        `/saved-profiles/${profileId}/apply/${props.questionnaire.slug}`,
        {},
        {
            onSuccess: () => {
                showProfilePicker.value = false;
                startFresh();
            },
        },
    );
}

const isInProgress = props.response?.status === 'in_progress';
const isCompleted = props.response?.status === 'completed';
const showStatus = computed(
    () => planningTogether.value || isInProgress || isCompleted,
);
</script>

<template>
    <Head :title="questionnaire.title" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Questionnaire"
            :title="questionnaire.title"
            :description="questionnaire.description ?? undefined"
            back-href="/questionnaires"
            back-label="Plan"
        >
            <div class="icon-tile h-14 w-14 text-3xl" aria-hidden="true">
                {{ questionnaire.emoji ?? '💕' }}
            </div>
        </PageHeader>

        <!-- Meta info -->
        <section
            :class="[
                'grid gap-3',
                questionnaire.estimated_minutes ? 'grid-cols-3' : 'grid-cols-2',
            ]"
            aria-label="About this questionnaire"
        >
            <StatCard
                label="Questions"
                :value="String(questionnaire.question_count)"
                description="to answer"
            />
            <StatCard
                v-if="questionnaire.estimated_minutes"
                label="Time"
                :value="`~${questionnaire.estimated_minutes}`"
                description="min"
            />
            <StatCard
                label="Streak"
                :value="String(current_streak)"
                description="days"
            />
        </section>

        <!-- Who is planning -->
        <section v-if="mode.selectable" class="space-y-3">
            <SectionHeader title="Who's planning?" />

            <div
                class="space-y-2.5"
                role="radiogroup"
                aria-label="Who's planning?"
            >
                <OptionCard
                    label="Just me"
                    description="Get your date idea as soon as you finish."
                    emoji="🙋"
                    value="solo"
                    :selected="selectedMode === 'solo'"
                    @select="selectedMode = 'solo'"
                />
                <OptionCard
                    v-if="mode.has_partner"
                    :label="`With ${mode.partner_name}`"
                    :description="coupleDescription"
                    emoji="💞"
                    value="couple"
                    :selected="selectedMode === 'couple'"
                    @select="selectedMode = 'couple'"
                />
                <Link
                    v-else
                    href="/partner"
                    class="flex items-center gap-3.5 rounded-2xl border border-dashed border-input p-4 text-sm text-muted-foreground transition-colors hover:border-foreground/40 hover:text-foreground"
                >
                    <UserPlus class="size-5 shrink-0" aria-hidden="true" />
                    <span class="min-w-0 flex-1"
                        >Connect your partner to plan a date together</span
                    >
                    <ChevronRight class="size-4 shrink-0" aria-hidden="true" />
                </Link>
            </div>
        </section>

        <!-- Status -->
        <section v-if="showStatus" class="space-y-4 card-premium p-5">
            <!-- Partner status -->
            <div v-if="planningTogether" class="flex items-center gap-3.5">
                <span class="icon-tile h-11 w-11" aria-hidden="true">
                    <Check
                        v-if="partner_completed"
                        class="size-5 text-success"
                    />
                    <Clock v-else class="size-5 text-muted-foreground" />
                </span>
                <p class="min-w-0 flex-1 font-medium text-foreground">
                    {{
                        partner_completed
                            ? 'Your partner has answered'
                            : 'Your partner has not answered yet'
                    }}
                </p>
            </div>

            <!-- Resume banner -->
            <div
                v-if="isInProgress"
                :class="[
                    'space-y-2.5',
                    planningTogether && 'border-t border-border pt-4',
                ]"
            >
                <p class="text-sm font-medium text-foreground">
                    Continue tonight's questionnaire
                </p>
                <ProgressBar
                    :value="response!.answered_count"
                    :max="questionnaire.question_count"
                />
                <p class="text-xs text-muted-foreground tabular-nums">
                    {{ response!.answered_count }} of
                    {{ questionnaire.question_count }} answered
                </p>
            </div>

            <!-- Completed banner -->
            <div
                v-if="isCompleted"
                :class="[
                    'flex items-center gap-3.5',
                    planningTogether && 'border-t border-border pt-4',
                ]"
            >
                <span class="icon-tile h-11 w-11" aria-hidden="true">
                    <Check class="size-5 text-success" />
                </span>
                <p class="min-w-0 flex-1 font-medium text-foreground">
                    You've completed this questionnaire
                </p>
            </div>
        </section>

        <!-- Actions -->
        <div class="space-y-3">
            <!-- Resume -->
            <PrimaryButton
                v-if="isInProgress"
                full-width
                @click="resumeQuestionnaire"
            >
                Continue questionnaire
            </PrimaryButton>

            <!-- Start fresh -->
            <PrimaryButton
                v-else-if="!isCompleted"
                full-width
                :loading="starting"
                @click="startFresh"
            >
                Start questionnaire
            </PrimaryButton>

            <!-- Restart Questionnaire -->
            <PrimaryButton
                v-if="isCompleted"
                full-width
                :loading="restarting"
                @click="restart"
            >
                <RotateCcw class="size-4" aria-hidden="true" />
                Restart questionnaire
            </PrimaryButton>

            <!-- View summary if completed -->
            <SecondaryButton
                v-if="isCompleted"
                variant="outline"
                full-width
                @click="
                    router.visit(
                        `/questionnaires/${questionnaire.slug}/summary`,
                    )
                "
            >
                View summary
            </SecondaryButton>

            <SecondaryButton
                v-else-if="isInProgress"
                variant="outline"
                full-width
                :loading="restarting"
                @click="restart"
            >
                <RotateCcw class="size-4" aria-hidden="true" />
                Restart questionnaire
            </SecondaryButton>

            <!-- Saved profiles -->
            <SecondaryButton
                v-if="saved_profiles.length > 0"
                variant="ghost"
                full-width
                @click="showProfilePicker = true"
            >
                Use saved profile
            </SecondaryButton>
        </div>

        <!-- Past Questionnaires -->
        <section v-if="past_attempts.length > 0" class="space-y-3">
            <SectionHeader title="Past results" />
            <p class="text-xs leading-relaxed text-muted-foreground">
                Only questionnaires where you and your partner both completed
                the answers.
            </p>
            <div class="card-premium p-2">
                <button
                    v-for="(attempt, index) in past_attempts"
                    :key="attempt.id"
                    type="button"
                    class="list-row w-full text-left focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                    @click="viewPastAttempt(attempt.id)"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                        >{{ questionnaire.emoji ?? '💕' }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-foreground">
                            {{ questionnaire.title }} #{{
                                past_attempts.length - index
                            }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                formatDate(attempt.completed_at) || 'Completed'
                            }}
                        </p>
                    </div>
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </section>
    </div>

    <!-- Profile picker modal -->
    <Transition name="fade">
        <div
            v-if="showProfilePicker"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-4"
            @click.self="showProfilePicker = false"
        >
            <div
                class="w-full max-w-md space-y-4 card-premium rounded-b-none p-6 sm:rounded-3xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="profile-picker-title"
            >
                <div class="flex items-center justify-between gap-4">
                    <h2
                        id="profile-picker-title"
                        class="text-xl font-semibold text-foreground"
                    >
                        Choose a saved profile
                    </h2>
                    <button
                        type="button"
                        class="-mr-2 flex size-10 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        aria-label="Close"
                        @click="showProfilePicker = false"
                    >
                        <X class="size-5" aria-hidden="true" />
                    </button>
                </div>
                <div class="-mx-2">
                    <button
                        v-for="profile in saved_profiles"
                        :key="profile.id"
                        type="button"
                        class="list-row w-full text-left focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none active:scale-[0.98]"
                        @click="applyProfile(profile.id)"
                    >
                        <span
                            class="icon-tile h-11 w-11 text-xl"
                            :style="{
                                boxShadow: `inset 0 0 0 1.5px ${profile.colour ?? 'var(--primary)'}`,
                            }"
                            aria-hidden="true"
                            >{{ profile.emoji ?? '💕' }}</span
                        >
                        <span
                            class="min-w-0 flex-1 truncate font-medium text-foreground"
                            >{{ profile.name }}</span
                        >
                        <ChevronRight
                            class="size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </button>
                </div>
                <SecondaryButton
                    variant="ghost"
                    full-width
                    @click="showProfilePicker = false"
                    >Cancel</SecondaryButton
                >
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
