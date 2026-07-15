<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
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
}

interface Props {
    questionnaire: QuestionnaireData;
    completed_at: string | null;
    current_streak: number;
    partner_completed: boolean;
}

const props = defineProps<Props>();

const completedTime = props.completed_at
    ? new Date(props.completed_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    : null;
</script>

<template>
    <Head title="Tonight is Planned ❤️" />

    <div class="relative min-h-screen overflow-hidden">
        <FloatingHearts />

        <div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-8 pb-24">
            <SuccessAnimation
                title="Tonight is Planned ❤️"
                message="Your answers have been saved."
                emoji="🎉"
            />

            <!-- Stats -->
            <div class="mt-6 w-full space-y-3">
                <div v-if="completedTime" class="flex items-center justify-between rounded-2xl bg-muted/50 px-4 py-3">
                    <span class="text-sm text-muted-foreground">Completed at</span>
                    <span class="font-semibold text-foreground">{{ completedTime }}</span>
                </div>

                <div class="flex items-center justify-between rounded-2xl bg-muted/50 px-4 py-3">
                    <span class="text-sm text-muted-foreground">Current streak</span>
                    <span class="font-semibold text-foreground">🔥 {{ current_streak }} days</span>
                </div>

                <div
                    :class="[
                        'flex items-center justify-between rounded-2xl px-4 py-3',
                        partner_completed ? 'bg-success/10' : 'bg-muted/50',
                    ]"
                >
                    <span class="text-sm text-muted-foreground">Partner status</span>
                    <span :class="['font-semibold', partner_completed ? 'text-success' : 'text-muted-foreground']">
                        {{ partner_completed ? '✅ Completed' : '⏳ Waiting' }}
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 w-full space-y-3">
                <PrimaryButton
                    v-if="partner_completed"
                    full-width
                    @click="router.visit(`/questionnaires/${questionnaire.slug}/partner-answers`)"
                >
                    💕 See Partner's Answers
                </PrimaryButton>

                <PrimaryButton
                    v-else
                    full-width
                    @click="router.visit(`/questionnaires/${questionnaire.slug}/summary`)"
                >
                    View Summary
                </PrimaryButton>

                <SecondaryButton
                    v-if="partner_completed"
                    full-width
                    @click="router.visit(`/questionnaires/${questionnaire.slug}/summary`)"
                >
                    View My Summary
                </SecondaryButton>

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
