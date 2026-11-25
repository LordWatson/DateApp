<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ChevronLeft,
    Copy,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import OptionForm from '@/components/admin/OptionForm.vue';
import type { EditableOption } from '@/components/admin/OptionForm.vue';
import QuestionForm from '@/components/admin/QuestionForm.vue';
import type { EditableQuestion } from '@/components/admin/QuestionForm.vue';
import QuestionnaireModeFields from '@/components/admin/QuestionnaireModeFields.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

interface Question extends EditableQuestion {
    options: EditableOption[];
}

interface Questionnaire {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    emoji: string | null;
    status: string;
    visibility: string;
    estimated_minutes: number | null;
    display_order: number;
    is_seasonal: boolean;
    is_solo: boolean;
    is_intimacy: boolean;
    partner_optional: boolean;
    active_from: string | null;
    active_until: string | null;
    questions: Question[];
}

const props = defineProps<{
    questionnaire: Questionnaire;
    signalOptions: string[];
}>();

const form = useForm({
    title: props.questionnaire.title,
    description: props.questionnaire.description ?? '',
    emoji: props.questionnaire.emoji ?? '',
    status: props.questionnaire.status,
    visibility: props.questionnaire.visibility,
    estimated_minutes: props.questionnaire.estimated_minutes,
    display_order: props.questionnaire.display_order,
    is_solo: props.questionnaire.is_solo,
    partner_optional: props.questionnaire.partner_optional,
    is_intimacy: props.questionnaire.is_intimacy,
    is_seasonal: props.questionnaire.is_seasonal,
    // The server sends a full timestamp; a date input wants YYYY-MM-DD.
    active_from: props.questionnaire.active_from?.slice(0, 10) ?? '',
    active_until: props.questionnaire.active_until?.slice(0, 10) ?? '',
});

const save = () => {
    form.put(admin.questionnaires.update(props.questionnaire.id).url, {
        preserveScroll: true,
    });
};

// One inline form open at a time: 'new', or the id being edited.
const questionForm = ref<number | 'new' | null>(null);
const optionForm = ref<{ question: number; option: number | 'new' } | null>(
    null,
);

const hasOptions = (question: Question) =>
    ['single_choice', 'multiple_choice'].includes(question.type);

/** Swaps an item with its neighbour and saves the whole list's order. */
const move = (
    type: 'questions' | 'options',
    items: Array<{ id: number }>,
    index: number,
    delta: -1 | 1,
) => {
    const target = index + delta;

    if (target < 0 || target >= items.length) {
        return;
    }

    const ids = items.map((item) => item.id);
    [ids[index], ids[target]] = [ids[target], ids[index]];

    router.post(
        admin.questionnaires.reorder().url,
        { type, order: ids.map((id, i) => ({ id, display_order: i })) },
        { preserveScroll: true },
    );
};

const duplicateOption = (optionId: number) => {
    router.post(
        admin.options.duplicate(optionId).url,
        {},
        { preserveScroll: true },
    );
};

const pendingDelete = ref<{ label: string; url: string } | null>(null);
const deleteOpen = ref(false);

const askDelete = (label: string, url: string) => {
    pendingDelete.value = { label, url };
    deleteOpen.value = true;
};

const confirmDelete = () => {
    if (pendingDelete.value) {
        router.delete(pendingDelete.value.url, { preserveScroll: true });
    }
};

const iconButton =
    'flex size-8 items-center justify-center rounded-full text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary disabled:pointer-events-none disabled:opacity-30';
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <Link
                :href="admin.questionnaires.index().url"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                ><ChevronLeft
                    class="size-4"
                    aria-hidden="true"
                />Questionnaires</Link
            >
            <h1 class="text-2xl font-semibold text-foreground">
                Edit: {{ questionnaire.title }}
            </h1>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Settings -->
            <div class="lg:col-span-1">
                <form
                    @submit.prevent="save"
                    class="space-y-4 card-premium rounded-2xl p-6"
                >
                    <h2 class="text-lg font-semibold text-foreground">
                        Settings
                    </h2>

                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <label
                                for="q-emoji"
                                class="text-xs text-muted-foreground"
                                >Emoji</label
                            >
                            <input
                                id="q-emoji"
                                v-model="form.emoji"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                        <div class="col-span-3">
                            <label
                                for="q-title"
                                class="text-xs text-muted-foreground"
                                >Title</label
                            >
                            <input
                                id="q-title"
                                v-model="form.title"
                                required
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                    </div>
                    <p class="font-mono text-xs text-muted-foreground">
                        /questionnaires/{{ questionnaire.slug }}
                    </p>

                    <div>
                        <label
                            for="q-description"
                            class="text-xs text-muted-foreground"
                            >Description</label
                        >
                        <textarea
                            id="q-description"
                            v-model="form.description"
                            rows="2"
                            class="mt-1 input-premium px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                for="q-status"
                                class="text-xs text-muted-foreground"
                                >Status</label
                            >
                            <select
                                id="q-status"
                                v-model="form.status"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div>
                            <label
                                for="q-visibility"
                                class="text-xs text-muted-foreground"
                                >Visibility</label
                            >
                            <select
                                id="q-visibility"
                                v-model="form.visibility"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            >
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                for="q-minutes"
                                class="text-xs text-muted-foreground"
                                >Est. minutes</label
                            >
                            <input
                                id="q-minutes"
                                v-model.number="form.estimated_minutes"
                                type="number"
                                min="1"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label
                                for="q-order"
                                class="text-xs text-muted-foreground"
                                >Order</label
                            >
                            <input
                                id="q-order"
                                v-model.number="form.display_order"
                                type="number"
                                min="0"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <QuestionnaireModeFields
                        v-model:is-solo="form.is_solo"
                        v-model:partner-optional="form.partner_optional"
                        v-model:is-intimacy="form.is_intimacy"
                    />

                    <div class="flex items-center gap-2">
                        <input
                            id="seasonal"
                            v-model="form.is_seasonal"
                            type="checkbox"
                            class="size-4 rounded accent-primary"
                        />
                        <label for="seasonal" class="text-sm text-foreground"
                            >Seasonal</label
                        >
                    </div>

                    <div v-if="form.is_seasonal" class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                for="q-from"
                                class="text-xs text-muted-foreground"
                                >Active from</label
                            >
                            <input
                                id="q-from"
                                v-model="form.active_from"
                                type="date"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label
                                for="q-until"
                                class="text-xs text-muted-foreground"
                                >Active until</label
                            >
                            <input
                                id="q-until"
                                v-model="form.active_until"
                                type="date"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <p
                        v-if="form.hasErrors"
                        role="alert"
                        class="text-xs text-destructive"
                    >
                        {{ Object.values(form.errors)[0] }}
                    </p>

                    <Button
                        size="sm"
                        type="submit"
                        :disabled="form.processing"
                        class="w-full"
                    >
                        Save settings
                    </Button>
                </form>
            </div>

            <!-- Questions builder -->
            <div class="space-y-4 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        Questions ({{ questionnaire.questions.length }})
                    </h2>
                    <Button size="sm" @click="questionForm = 'new'">
                        <Plus class="size-4" aria-hidden="true" />Add question
                    </Button>
                </div>

                <div
                    v-if="questionForm === 'new'"
                    class="space-y-3 card-premium rounded-2xl p-5"
                >
                    <h3 class="text-lg font-semibold text-foreground">
                        New question
                    </h3>
                    <QuestionForm
                        :questionnaire-id="questionnaire.id"
                        :next-order="questionnaire.questions.length"
                        @done="questionForm = null"
                    />
                </div>

                <div
                    v-for="(question, qIndex) in questionnaire.questions"
                    :key="question.id"
                    class="card-premium rounded-2xl p-5"
                >
                    <QuestionForm
                        v-if="questionForm === question.id"
                        :questionnaire-id="questionnaire.id"
                        :question="question"
                        :next-order="question.display_order"
                        @done="questionForm = null"
                    />

                    <div v-else class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                v-if="question.emoji"
                                class="text-xl"
                                aria-hidden="true"
                                >{{ question.emoji }}</span
                            >
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-foreground">
                                    {{ question.title }}
                                </p>
                                <span class="text-xs text-muted-foreground"
                                    >{{ question.type.replace('_', ' ') }}
                                    ·
                                    {{
                                        question.required
                                            ? 'required'
                                            : 'optional'
                                    }}</span
                                >
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center">
                            <button
                                type="button"
                                :class="iconButton"
                                :disabled="qIndex === 0"
                                :aria-label="`Move ${question.title} up`"
                                @click="
                                    move(
                                        'questions',
                                        questionnaire.questions,
                                        qIndex,
                                        -1,
                                    )
                                "
                            >
                                <ArrowUp class="size-4" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                :class="iconButton"
                                :disabled="
                                    qIndex ===
                                    questionnaire.questions.length - 1
                                "
                                :aria-label="`Move ${question.title} down`"
                                @click="
                                    move(
                                        'questions',
                                        questionnaire.questions,
                                        qIndex,
                                        1,
                                    )
                                "
                            >
                                <ArrowDown class="size-4" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                :class="iconButton"
                                :aria-label="`Edit ${question.title}`"
                                @click="questionForm = question.id"
                            >
                                <Pencil class="size-4" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                :class="[iconButton, 'hover:text-destructive']"
                                :aria-label="`Delete ${question.title}`"
                                @click="
                                    askDelete(
                                        question.title,
                                        admin.questions.destroy(question.id)
                                            .url,
                                    )
                                "
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <!-- Options -->
                    <div v-if="hasOptions(question)" class="mt-3 space-y-2">
                        <template
                            v-for="(option, oIndex) in question.options"
                            :key="option.id"
                        >
                            <OptionForm
                                v-if="
                                    optionForm?.question === question.id &&
                                    optionForm.option === option.id
                                "
                                :question-id="question.id"
                                :option="option"
                                :next-order="option.display_order"
                                :signal-options="signalOptions"
                                @done="optionForm = null"
                            />
                            <div
                                v-else
                                class="flex items-center gap-2 rounded-xl bg-muted py-1 pr-1 pl-3"
                            >
                                <span
                                    v-if="option.emoji"
                                    class="text-sm"
                                    aria-hidden="true"
                                    >{{ option.emoji }}</span
                                >
                                <span
                                    class="min-w-0 flex-1 truncate text-sm text-foreground"
                                    >{{ option.title }}</span
                                >
                                <span
                                    v-for="signal in option.signals ?? []"
                                    :key="signal"
                                    class="badge-soft"
                                    >{{ signal }}</span
                                >
                                <div class="flex shrink-0 items-center">
                                    <button
                                        type="button"
                                        :class="iconButton"
                                        :disabled="oIndex === 0"
                                        :aria-label="`Move ${option.title} up`"
                                        @click="
                                            move(
                                                'options',
                                                question.options,
                                                oIndex,
                                                -1,
                                            )
                                        "
                                    >
                                        <ArrowUp
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </button>
                                    <button
                                        type="button"
                                        :class="iconButton"
                                        :disabled="
                                            oIndex ===
                                            question.options.length - 1
                                        "
                                        :aria-label="`Move ${option.title} down`"
                                        @click="
                                            move(
                                                'options',
                                                question.options,
                                                oIndex,
                                                1,
                                            )
                                        "
                                    >
                                        <ArrowDown
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </button>
                                    <button
                                        type="button"
                                        :class="iconButton"
                                        :aria-label="`Edit ${option.title}`"
                                        @click="
                                            optionForm = {
                                                question: question.id,
                                                option: option.id,
                                            }
                                        "
                                    >
                                        <Pencil
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </button>
                                    <button
                                        type="button"
                                        :class="iconButton"
                                        :aria-label="`Duplicate ${option.title}`"
                                        @click="duplicateOption(option.id)"
                                    >
                                        <Copy
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </button>
                                    <button
                                        type="button"
                                        :class="[
                                            iconButton,
                                            'hover:text-destructive',
                                        ]"
                                        :aria-label="`Delete ${option.title}`"
                                        @click="
                                            askDelete(
                                                option.title,
                                                admin.options.destroy(option.id)
                                                    .url,
                                            )
                                        "
                                    >
                                        <Trash2
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </div>
                            </div>
                        </template>

                        <OptionForm
                            v-if="
                                optionForm?.question === question.id &&
                                optionForm.option === 'new'
                            "
                            :question-id="question.id"
                            :next-order="question.options.length"
                            :signal-options="signalOptions"
                            @done="optionForm = null"
                        />
                        <button
                            v-else
                            type="button"
                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground hover:underline"
                            @click="
                                optionForm = {
                                    question: question.id,
                                    option: 'new',
                                }
                            "
                        >
                            <Plus class="size-4" aria-hidden="true" />Add option
                        </button>
                    </div>
                </div>

                <p
                    v-if="!questionnaire.questions.length"
                    class="card-premium rounded-2xl py-8 text-center text-sm text-muted-foreground"
                >
                    No questions yet. Add your first question above.
                </p>
            </div>
        </div>

        <ConfirmDialog
            v-model:open="deleteOpen"
            :title="`Delete '${pendingDelete?.label ?? ''}'?`"
            description="This cannot be undone. A question that people have already answered is kept."
            @confirm="confirmDelete"
        />
    </div>
</template>
