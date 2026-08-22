<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import MultipleChoiceQuestion from '@/components/questions/MultipleChoiceQuestion.vue';
import SingleChoiceQuestion from '@/components/questions/SingleChoiceQuestion.vue';
import SliderQuestion from '@/components/questions/SliderQuestion.vue';
import TextareaQuestion from '@/components/questions/TextareaQuestion.vue';
import TextQuestion from '@/components/questions/TextQuestion.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface Option {
    id: number;
    title: string;
    description: string | null;
    emoji: string | null;
    value: string;
}

interface Question {
    id: number;
    title: string;
    description: string | null;
    emoji: string | null;
    type: string;
    required: boolean;
    minimum_value: number | null;
    maximum_value: number | null;
    step_value: number | null;
    unit: string | null;
    display_order: number;
    options: Option[];
}

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
    estimated_minutes: number | null;
    is_solo?: boolean;
}

interface Progress {
    current: number;
    total: number;
    percentage: number;
    answered_count: number;
}

interface Props {
    questionnaire: QuestionnaireData;
    question: Question;
    current_answer: string | string[] | null;
    progress: Progress;
    has_previous: boolean;
    has_next: boolean;
    is_last: boolean;
}

const props = defineProps<Props>();

const answer = ref<string | string[] | null>(props.current_answer);
const saving = ref(false);
const saveError = ref(false);
const continueVisible = ref(!!props.current_answer);

const hasAnswer = computed(() => {
    if (Array.isArray(answer.value)) {
        return answer.value.length > 0;
    }

    return answer.value !== null && answer.value !== '';
});

const canContinue = computed(() => {
    if (!props.question.required) {
return true;
}

    return hasAnswer.value;
});

const minutesLeft = computed(() => {
    if (!props.questionnaire.estimated_minutes) {
return null;
}

    const remaining = props.progress.total - props.progress.current;
    const perQuestion = props.questionnaire.estimated_minutes / props.progress.total;

    return Math.max(1, Math.round(remaining * perQuestion));
});

let saveTimeout: ReturnType<typeof setTimeout> | null = null;
let advanceTimeout: ReturnType<typeof setTimeout> | null = null;
const AUTO_ADVANCE_DELAY_MS = 400;

watch(answer, (newVal) => {
    if (newVal !== null && newVal !== '' && !(Array.isArray(newVal) && newVal.length === 0)) {
        continueVisible.value = true;
    }

    if (saveTimeout) {
clearTimeout(saveTimeout);
}

    saveTimeout = setTimeout(() => {
        saveAnswer().then(() => {
            const currentValue = typeof answer.value === 'string' ? answer.value : null;
            const isCustomBudget = currentValue?.startsWith('custom_amount') ?? false;

            if (props.question.type === 'single_choice' && hasAnswer.value && !isCustomBudget) {
                if (advanceTimeout) {
                    clearTimeout(advanceTimeout);
                }

                advanceTimeout = setTimeout(() => {
                    goNext();
                }, AUTO_ADVANCE_DELAY_MS);
            }
        });
    }, 300);
});

async function saveAnswer(): Promise<void> {
    saving.value = true;
    saveError.value = false;

    try {
        await axios.post(
            `/questionnaires/${props.questionnaire.slug}/question/${props.question.display_order}/answer`,
            { value: answer.value },
        );
    } catch {
        saveError.value = true;
        setTimeout(() => saveAnswer(), 3000);
    } finally {
        saving.value = false;
    }
}

function goBack(): void {
    if (props.has_previous) {
        router.visit(`/questionnaires/${props.questionnaire.slug}/question/${props.question.display_order - 1}`);
    } else {
        router.visit(`/questionnaires/${props.questionnaire.slug}`);
    }
}

function goNext(): void {
    if (!canContinue.value) {
return;
}

    if (props.is_last) {
        const nextPath = props.questionnaire.is_solo ? 'location' : 'summary';
        router.visit(`/questionnaires/${props.questionnaire.slug}/${nextPath}`);
    } else {
        router.visit(`/questionnaires/${props.questionnaire.slug}/question/${props.question.display_order + 1}`);
    }
}
</script>

<template>
    <Head :title="`Question ${progress.current} of ${progress.total}`" />

    <div class="flex min-h-screen flex-col px-4 py-6 pb-32">
        <!-- Progress header -->
        <div class="mb-6 space-y-3">
            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span class="font-medium">Question {{ progress.current }} of {{ progress.total }}</span>
                <span v-if="minutesLeft">~{{ minutesLeft }} min left</span>
            </div>
            <ProgressBar :value="progress.current" :max="progress.total" />
        </div>

        <!-- Question card -->
        <div class="flex-1 space-y-6">
            <div class="card-premium p-6 space-y-4">
                <div v-if="question.emoji" class="flex justify-center">
                    <span class="text-6xl" aria-hidden="true">{{ question.emoji }}</span>
                </div>
                <div class="space-y-2 text-center">
                    <h2 class="text-xl font-semibold leading-snug text-foreground">{{ question.title }}</h2>
                    <p v-if="question.description" class="text-sm text-muted-foreground">{{ question.description }}</p>
                    <p v-if="!question.required" class="text-xs text-muted-foreground italic">Optional — you may skip this</p>
                </div>
            </div>

            <!-- Answer component -->
            <Transition name="slide-fade" mode="out-in">
                <div :key="question.id">
                    <SingleChoiceQuestion
                        v-if="question.type === 'single_choice'"
                        v-model="answer as string"
                        :options="question.options"
                    />
                    <MultipleChoiceQuestion
                        v-else-if="question.type === 'multiple_choice'"
                        v-model="answer as string[]"
                        :options="question.options"
                    />
                    <SliderQuestion
                        v-else-if="question.type === 'slider'"
                        v-model="answer as string"
                        :minimum-value="question.minimum_value"
                        :maximum-value="question.maximum_value"
                        :step-value="question.step_value"
                        :unit="question.unit"
                    />
                    <TextQuestion
                        v-else-if="question.type === 'text'"
                        v-model="answer as string"
                    />
                    <TextareaQuestion
                        v-else-if="question.type === 'textarea'"
                        v-model="answer as string"
                    />
                </div>
            </Transition>

            <!-- Save status -->
            <div class="flex items-center justify-center gap-2 text-xs">
                <span v-if="saving" class="text-muted-foreground">Saving…</span>
                <span v-else-if="saveError" class="text-destructive">⚠️ Connection issue — retrying…</span>
                <span v-else-if="hasAnswer" class="text-success">✓ Saved</span>
            </div>
        </div>

        <!-- Navigation -->
        <div class="fixed inset-x-0 bottom-0 space-y-3 bg-background/95 px-4 pb-8 pt-4 backdrop-blur-sm">
            <Transition name="slide-up">
                <PrimaryButton
                    v-if="continueVisible || !question.required"
                    full-width
                    :disabled="!canContinue"
                    @click="goNext"
                >
                    {{ is_last ? 'Review Answers ❤️' : 'Continue' }}
                </PrimaryButton>
            </Transition>

            <SecondaryButton full-width @click="goBack">
                {{ has_previous ? '← Back' : '← Introduction' }}
            </SecondaryButton>
        </div>
    </div>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.25s ease;
}
.slide-fade-enter-from {
    opacity: 0;
    transform: translateX(20px);
}
.slide-fade-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>
