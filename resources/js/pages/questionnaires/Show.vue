<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
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

interface Props {
    questionnaire: QuestionnaireData;
    response: ResponseData | null;
    partner_completed: boolean;
    current_streak: number;
    saved_profiles: SavedProfile[];
}

const props = defineProps<Props>();
const starting = ref(false);
const showProfilePicker = ref(false);

function startFresh(): void {
    starting.value = true;
    router.post(`/questionnaires/${props.questionnaire.slug}/start`, {}, {
        onFinish: () => {
            starting.value = false;
        },
    });
}

function resumeQuestionnaire(): void {
    if (props.response) {
        router.visit(`/questionnaires/${props.questionnaire.slug}/question/${props.response.resume_order}`);
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
</script>

<template>
    <Head :title="questionnaire.title" />

    <div class="relative min-h-screen overflow-hidden">
        <FloatingHearts />

        <div class="relative z-10 flex min-h-screen flex-col px-4 py-8 pb-24">
            <!-- Header emoji -->
            <div class="flex flex-1 flex-col items-center justify-center space-y-8 text-center">
                <div class="animate-success-pop text-8xl">
                    {{ questionnaire.emoji ?? '💕' }}
                </div>

                <div class="space-y-3">
                    <h1 class="text-3xl font-semibold text-foreground">{{ questionnaire.title }}</h1>
                    <p v-if="questionnaire.description" class="text-base text-muted-foreground leading-relaxed">
                        {{ questionnaire.description }}
                    </p>
                </div>

                <!-- Meta info -->
                <div class="flex items-center gap-6 text-sm text-muted-foreground">
                    <div class="flex items-center gap-1.5">
                        <span>📝</span>
                        <span>{{ questionnaire.question_count }} questions</span>
                    </div>
                    <div v-if="questionnaire.estimated_minutes" class="flex items-center gap-1.5">
                        <span>⏱️</span>
                        <span>~{{ questionnaire.estimated_minutes }} min</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>🔥</span>
                        <span>{{ current_streak }} day streak</span>
                    </div>
                </div>

                <!-- Partner status -->
                <div
                    :class="[
                        'flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-medium',
                        partner_completed
                            ? 'bg-success/10 text-success'
                            : 'bg-muted text-muted-foreground',
                    ]"
                >
                    <span>{{ partner_completed ? '✅' : '⏳' }}</span>
                    <span>{{ partner_completed ? 'Partner has completed this' : 'Waiting for your partner' }}</span>
                </div>

                <!-- Resume banner -->
                <div
                    v-if="isInProgress"
                    class="w-full rounded-3xl border-2 border-warning/30 bg-warning/10 p-4 text-center"
                >
                    <p class="text-sm font-semibold text-warning">Continue Tonight's Questionnaire ❤️</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ response!.answered_count }} of {{ questionnaire.question_count }} answered
                    </p>
                </div>

                <!-- Completed banner -->
                <div
                    v-if="isCompleted"
                    class="w-full rounded-3xl border-2 border-success/30 bg-success/10 p-4 text-center"
                >
                    <p class="text-sm font-semibold text-success">You've completed this questionnaire ✓</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 space-y-3">
                <!-- Resume -->
                <PrimaryButton
                    v-if="isInProgress"
                    full-width
                    @click="resumeQuestionnaire"
                >
                    Continue Questionnaire ❤️
                </PrimaryButton>

                <!-- Start fresh / restart -->
                <PrimaryButton
                    v-else
                    full-width
                    :loading="starting"
                    @click="startFresh"
                >
                    {{ isCompleted ? 'Retake Questionnaire ❤️' : 'Start Questionnaire ❤️' }}
                </PrimaryButton>

                <!-- View summary if completed -->
                <SecondaryButton
                    v-if="isCompleted"
                    full-width
                    @click="router.visit(`/questionnaires/${questionnaire.slug}/summary`)"
                >
                    View Summary
                </SecondaryButton>

                <!-- Saved profiles -->
                <SecondaryButton
                    v-if="saved_profiles.length > 0"
                    full-width
                    @click="showProfilePicker = true"
                >
                    Use Saved Profile
                </SecondaryButton>
            </div>
        </div>

        <!-- Profile picker modal -->
        <Transition name="fade">
            <div
                v-if="showProfilePicker"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 px-4 pb-8"
                @click.self="showProfilePicker = false"
            >
                <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-xl space-y-4">
                    <h3 class="text-lg font-semibold text-foreground">Choose a Saved Profile</h3>
                    <div class="space-y-2">
                        <button
                            v-for="profile in saved_profiles"
                            :key="profile.id"
                            class="flex w-full items-center gap-3 rounded-2xl p-4 text-left transition-all hover:bg-muted active:scale-[0.98]"
                            :style="{ borderLeft: `4px solid ${profile.colour ?? '#EC4899'}` }"
                            @click="applyProfile(profile.id)"
                        >
                            <span class="text-2xl">{{ profile.emoji ?? '💕' }}</span>
                            <span class="font-medium text-foreground">{{ profile.name }}</span>
                        </button>
                    </div>
                    <SecondaryButton full-width @click="showProfilePicker = false">Cancel</SecondaryButton>
                </div>
            </div>
        </Transition>
    </div>
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
