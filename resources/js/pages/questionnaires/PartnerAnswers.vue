<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
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
    <Head title="Partner's answers" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            :eyebrow="questionnaire.title"
            title="Partner's answers"
            :back-href="`/questionnaires/${questionnaire.slug}`"
            back-label="Back to questionnaire"
        >
            <template #description>
                See how you and {{ partner_name }} compare.
                <span
                    v-if="props.is_past_attempt && props.completed_at"
                    class="block text-xs"
                >
                    Completed on {{ formatDate(props.completed_at) }}
                </span>
            </template>
        </PageHeader>

        <section class="space-y-3">
            <!-- Legend -->
            <div
                class="flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-muted-foreground"
            >
                <span class="inline-flex items-center gap-2">
                    <span
                        class="size-2 rounded-full bg-primary"
                        aria-hidden="true"
                    ></span>
                    You
                </span>
                <span class="inline-flex items-center gap-2">
                    <span
                        class="size-2 rounded-full bg-gold"
                        aria-hidden="true"
                    ></span>
                    {{ partner_name }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <Check class="size-3.5 text-success" aria-hidden="true" />
                    Match
                </span>
            </div>

            <!-- Answer comparison cards -->
            <div
                v-for="group in grouped_answers"
                :key="group.question.id"
                class="space-y-4 card-premium p-5"
            >
                <div class="flex items-start gap-3.5">
                    <span
                        v-if="group.question.emoji"
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                    >
                        {{ group.question.emoji }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-xs font-medium text-muted-foreground tabular-nums"
                        >
                            Q{{ group.question.display_order }}
                        </p>
                        <p
                            class="mt-0.5 text-sm leading-snug font-medium text-foreground"
                        >
                            {{ group.question.title }}
                        </p>
                    </div>
                    <span
                        v-if="answersMatch(group)"
                        class="badge-soft shrink-0 text-success"
                    >
                        <Check class="size-3" aria-hidden="true" />
                        Match
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-muted/60 p-3">
                        <p
                            class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                        >
                            <span
                                class="size-2 shrink-0 rounded-full bg-primary"
                                aria-hidden="true"
                            ></span>
                            You
                        </p>
                        <p class="text-sm leading-snug text-foreground">
                            {{
                                formatAnswers(
                                    group.my_answers,
                                    group.question.type,
                                )
                            }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-muted/60 p-3">
                        <p
                            class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                        >
                            <span
                                class="size-2 shrink-0 rounded-full bg-gold"
                                aria-hidden="true"
                            ></span>
                            <span class="truncate">{{ partner_name }}</span>
                        </p>
                        <p class="text-sm leading-snug text-foreground">
                            {{
                                formatAnswers(
                                    group.partner_answers,
                                    group.question.type,
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Actions -->
        <div class="space-y-3">
            <PrimaryButton
                v-if="!props.is_past_attempt"
                full-width
                @click="
                    router.visit(
                        `/questionnaires/${questionnaire.slug}/compatibility`,
                    )
                "
            >
                View compatibility score
            </PrimaryButton>
            <SecondaryButton
                variant="outline"
                full-width
                @click="router.visit('/dashboard')"
            >
                Return home
            </SecondaryButton>
        </div>
    </div>
</template>
