<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface AnswerData {
    value: string | null;
    option_title: string | null;
    option_emoji: string | null;
}

interface QuestionData {
    id: number;
    title: string;
    emoji: string | null;
    type: string;
    display_order: number;
}

interface GroupedAnswer {
    question: QuestionData;
    my_answers: AnswerData[];
    partner_answers: AnswerData[];
}

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
}

interface Props {
    questionnaire: QuestionnaireData;
    partner_name: string;
    grouped_answers: GroupedAnswer[];
    completed_at?: string | null;
    is_past_attempt?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    completed_at: null,
    is_past_attempt: false,
});

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

function formatAnswers(answers: AnswerData[], type: string): string {
    if (answers.length === 0) {
        return 'Not answered';
    }

    if (type === 'text' || type === 'textarea' || type === 'slider') {
        return answers[0]?.value ?? 'Not answered';
    }

    return answers

        .map((a) => {
            const label = a.option_title ?? a.value ?? '';

            return a.option_emoji ? `${a.option_emoji} ${label}` : label;
        })
        .join(', ');
}

function answersMatch(group: GroupedAnswer): boolean {

    const mine = formatAnswers(group.my_answers, group.question.type);
    const theirs = formatAnswers(group.partner_answers, group.question.type);

    return mine === theirs && mine !== 'Not answered';
}
</script>

<template>
    <Head title="Partner's Answers" />

    <div class="space-y-4 px-4 py-6 pb-32">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-foreground">💕 Partner's Answers</h1>
            <p class="text-sm text-muted-foreground">See how you and {{ partner_name }} compare.</p>
            <p v-if="props.is_past_attempt && props.completed_at" class="text-xs text-muted-foreground">
                Completed on {{ formatDate(props.completed_at) }}
            </p>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-4 rounded-2xl bg-card p-4 shadow-sm">
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <span class="inline-block h-3 w-3 rounded-full bg-primary/20 ring-2 ring-primary"></span>
                You
            </div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <span class="inline-block h-3 w-3 rounded-full bg-secondary/20 ring-2 ring-secondary"></span>
                {{ partner_name }}
            </div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <span class="text-success text-base">✓</span>
                Match
            </div>
        </div>

        <!-- Answer comparison cards -->
        <div
            v-for="group in grouped_answers"
            :key="group.question.id"
            class="card-premium p-5 space-y-3"
            :class="answersMatch(group) ? 'ring-2 ring-success/40' : ''"
        >
            <div class="flex items-start gap-3">
                <span v-if="group.question.emoji" class="shrink-0 text-2xl" aria-hidden="true">
                    {{ group.question.emoji }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Q{{ group.question.display_order }}
                    </p>
                    <p class="text-sm font-semibold text-foreground">{{ group.question.title }}</p>
                </div>
                <span v-if="answersMatch(group)" class="shrink-0 text-success text-lg" aria-label="Match">✓</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-primary/10 p-3">
                    <p class="mb-1 text-xs font-semibold text-primary">You</p>
                    <p class="text-sm text-foreground">
                        {{ formatAnswers(group.my_answers, group.question.type) }}
                    </p>
                </div>
                <div class="rounded-2xl bg-secondary/10 p-3">
                    <p class="mb-1 text-xs font-semibold text-secondary">{{ partner_name }}</p>
                    <p class="text-sm text-foreground">
                        {{ formatAnswers(group.partner_answers, group.question.type) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-3 pt-2">
            <PrimaryButton
                v-if="!props.is_past_attempt"
                full-width
                @click="router.visit(`/questionnaires/${questionnaire.slug}/compatibility`)"
            >
                View Compatibility Score 💕
            </PrimaryButton>
            <SecondaryButton
                full-width
                @click="router.visit(`/questionnaires/${questionnaire.slug}`)"
            >
                Back to Questionnaire
            </SecondaryButton>
            <SecondaryButton full-width @click="router.visit('/dashboard')">
                Return Home ❤️
            </SecondaryButton>
        </div>
    </div>
</template>
