<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface Option {
    id: number;
    title: string;
    emoji: string | null;
    value: string;
}

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
    options: Option[];
}

interface GroupedAnswer {
    question: QuestionData;
    answers: AnswerData[];
}

interface SavedProfile {
    id: number;
    name: string;
    emoji: string | null;
    colour: string | null;
}

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
}

interface Props {
    questionnaire: QuestionnaireData;
    response_status: string;
    grouped_answers: GroupedAnswer[];
    saved_profiles: SavedProfile[];
}

const props = defineProps<Props>();

const showSaveModal = ref(false);
const profileName = ref('');
const profileEmoji = ref('❤️');
const profileColour = ref('#EC4899');
const saving = ref(false);
const finishing = ref(false);

const emojiOptions = ['❤️', '🎬', '🏨', '🌧️', '🎉', '💕', '✨', '🌹'];
const colourOptions = ['#EC4899', '#9333EA', '#10B981', '#F59E0B', '#3B82F6', '#F43F5E'];

function formatAnswer(group: GroupedAnswer): string {
    if (group.answers.length === 0) {
        return 'Not answered';
    }

    if (group.question.type === 'text' || group.question.type === 'textarea' || group.question.type === 'slider') {
        return group.answers[0]?.value ?? 'Not answered';
    }

    return group.answers
        .map((a) => {
            const label = a.option_title ?? a.value ?? '';

            return a.option_emoji ? `${a.option_emoji} ${label}` : label;
        })
        .join(', ');
}

function editAnswer(order: number): void {
    router.visit(`/questionnaires/${props.questionnaire.slug}/question/${order}`);
}

async function saveProfile(): Promise<void> {
    if (!profileName.value.trim()) {
        return;
    }

    saving.value = true;

    try {
        await fetch('/saved-profiles', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                ),
            },
            body: JSON.stringify({
                name: profileName.value,
                emoji: profileEmoji.value,
                colour: profileColour.value,
                questionnaire_id: props.questionnaire.id,
            }),
        });
        showSaveModal.value = false;
    } finally {
        saving.value = false;
    }
}

function completeQuestionnaire(): void {
    finishing.value = true;
    router.post(`/questionnaires/${props.questionnaire.slug}/finish`, {}, {
        onFinish: () => {
            finishing.value = false;
        },
    });
}
</script>

<template>
    <Head title="Your Answers" />

    <div class="space-y-4 px-4 py-6 pb-32">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-foreground">Your Answers 📋</h1>
            <p class="text-sm text-muted-foreground">Review and edit before completing.</p>
        </div>

        <!-- Answer cards -->
        <div
            v-for="group in grouped_answers"
            :key="group.question.id"
            class="card-premium p-5"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0 flex-1">
                    <span v-if="group.question.emoji" class="shrink-0 text-2xl" aria-hidden="true">
                        {{ group.question.emoji }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Q{{ group.question.display_order }}
                        </p>
                        <p class="text-sm font-semibold text-foreground">{{ group.question.title }}</p>
                        <p class="mt-1 text-sm text-primary font-medium">{{ formatAnswer(group) }}</p>
                    </div>
                </div>
                <button
                    class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-medium text-primary hover:bg-primary/10 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                    @click="editAnswer(group.question.display_order)"
                >
                    Edit
                </button>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-3 pt-2">
            <SecondaryButton full-width @click="showSaveModal = true">
                💾 Save as Favourite Profile
            </SecondaryButton>

            <PrimaryButton
                full-width
                :loading="finishing"
                @click="completeQuestionnaire"
            >
                Complete Questionnaire ❤️
            </PrimaryButton>
        </div>
    </div>

    <!-- Save profile modal -->
    <Transition name="fade">
        <div
            v-if="showSaveModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 px-4 pb-8"
            @click.self="showSaveModal = false"
        >
            <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-xl space-y-5">
                <h3 class="text-lg font-semibold text-foreground">Save as Profile</h3>

                <div class="space-y-3">
                    <input
                        v-model="profileName"
                        type="text"
                        placeholder="Give this profile a name…"
                        class="w-full rounded-2xl border-2 border-border bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none"
                    />

                    <div>
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Choose an emoji</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="e in emojiOptions"
                                :key="e"
                                :class="[
                                    'rounded-xl p-2 text-xl transition-all',
                                    profileEmoji === e ? 'bg-primary/20 ring-2 ring-primary' : 'hover:bg-muted',
                                ]"
                                @click="profileEmoji = e"
                            >
                                {{ e }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Choose a colour</p>
                        <div class="flex gap-2">
                            <button
                                v-for="c in colourOptions"
                                :key="c"
                                :class="[
                                    'h-8 w-8 rounded-full transition-all',
                                    profileColour === c ? 'ring-2 ring-offset-2 ring-foreground scale-110' : '',
                                ]"
                                :style="{ backgroundColor: c }"
                                @click="profileColour = c"
                            />
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <PrimaryButton full-width :loading="saving" @click="saveProfile">
                        Save Profile
                    </PrimaryButton>
                    <SecondaryButton full-width @click="showSaveModal = false">Cancel</SecondaryButton>
                </div>
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
