<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus, X, ChevronLeft } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

interface QuestionOption {
    id: number;
    title: string;
    description: string | null;
    emoji: string | null;
    value: string;
    display_order: number;
}

interface Question {
    id: number;
    title: string;
    description: string | null;
    emoji: string | null;
    type: string;
    display_order: number;
    required: boolean;
    min_value: number | null;
    max_value: number | null;
    options: QuestionOption[];
}

interface Questionnaire {
    id: number;
    title: string;
    description: string | null;
    emoji: string | null;
    status: string;
    visibility: string;
    estimated_minutes: number | null;
    display_order: number;
    is_seasonal: boolean;
    active_from: string | null;
    active_until: string | null;
    questions: Question[];
}

const props = defineProps<{ questionnaire: Questionnaire }>();

const form = useForm({
    title: props.questionnaire.title,
    description: props.questionnaire.description ?? '',
    emoji: props.questionnaire.emoji ?? '',
    status: props.questionnaire.status,
    visibility: props.questionnaire.visibility,
    estimated_minutes: props.questionnaire.estimated_minutes,
    display_order: props.questionnaire.display_order,
    is_seasonal: props.questionnaire.is_seasonal,
    active_from: props.questionnaire.active_from ?? '',
    active_until: props.questionnaire.active_until ?? '',
});

const save = () => {
    form.put(route('admin.questionnaires.update', props.questionnaire.id));
};

// New question form
const showNewQuestion = ref(false);
const newQuestion = useForm({
    title: '',
    description: '',
    emoji: '',
    type: 'single_choice',
    display_order: props.questionnaire.questions.length,
    required: true,
    min_value: null as number | null,
    max_value: null as number | null,
});

const addQuestion = () => {
    newQuestion.post(route('admin.questions.store', props.questionnaire.id), {
        onSuccess: () => {
            showNewQuestion.value = false;
            newQuestion.reset();
        },
    });
};

const deleteQuestion = (questionId: number) => {
    router.delete(route('admin.questions.destroy', questionId));
};

// New option form
const showNewOption = ref<number | null>(null);
const newOption = useForm({
    title: '',
    description: '',
    emoji: '',
    value: '',
    display_order: 0,
});

const addOption = (questionId: number) => {
    newOption.post(route('admin.options.store', questionId), {
        onSuccess: () => {
            showNewOption.value = null;
            newOption.reset();
        },
    });
};

const deleteOption = (optionId: number) => {
    router.delete(route('admin.options.destroy', optionId));
};

const duplicateOption = (optionId: number) => {
    router.post(route('admin.options.duplicate', optionId));
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-4">
            <Link
                :href="route('admin.questionnaires.index')"
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
                            <label class="text-xs text-muted-foreground"
                                >Emoji</label
                            >
                            <input
                                v-model="form.emoji"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                        <div class="col-span-3">
                            <label class="text-xs text-muted-foreground"
                                >Title</label
                            >
                            <input
                                v-model="form.title"
                                required
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-muted-foreground"
                            >Description</label
                        >
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="mt-1 input-premium px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-muted-foreground"
                                >Status</label
                            >
                            <select
                                v-model="form.status"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-muted-foreground"
                                >Visibility</label
                            >
                            <select
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
                            <label class="text-xs text-muted-foreground"
                                >Est. minutes</label
                            >
                            <input
                                v-model.number="form.estimated_minutes"
                                type="number"
                                min="1"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-muted-foreground"
                                >Order</label
                            >
                            <input
                                v-model.number="form.display_order"
                                type="number"
                                min="0"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="seasonal"
                            v-model="form.is_seasonal"
                            type="checkbox"
                            class="size-4 rounded accent-primary"
                        />
                        <label for="seasonal" class="text-xs text-foreground"
                            >Seasonal</label
                        >
                    </div>

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

            <!-- Questions Builder -->
            <div class="space-y-4 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        Questions ({{ questionnaire.questions.length }})
                    </h2>
                    <Button
                        size="sm"
                        @click="showNewQuestion = !showNewQuestion"
                    >
                        <Plus class="size-4" aria-hidden="true" />Add question
                    </Button>
                </div>

                <!-- New Question Form -->
                <div
                    v-if="showNewQuestion"
                    class="space-y-3 card-premium rounded-2xl p-5"
                >
                    <h3 class="text-lg font-semibold text-foreground">
                        New question
                    </h3>
                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs text-muted-foreground"
                                >Emoji</label
                            >
                            <input
                                v-model="newQuestion.emoji"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                        <div class="col-span-3">
                            <label class="text-xs text-muted-foreground"
                                >Title</label
                            >
                            <input
                                v-model="newQuestion.title"
                                required
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-muted-foreground"
                                >Type</label
                            >
                            <select
                                v-model="newQuestion.type"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            >
                                <option value="single_choice">
                                    Single choice
                                </option>
                                <option value="multiple_choice">
                                    Multiple choice
                                </option>
                                <option value="slider">Slider</option>
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-muted-foreground"
                                >Order</label
                            >
                            <input
                                v-model.number="newQuestion.display_order"
                                type="number"
                                min="0"
                                class="mt-1 input-premium px-3 py-2 text-sm"
                            />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            id="required"
                            v-model="newQuestion.required"
                            type="checkbox"
                            class="size-4 rounded accent-primary"
                        />
                        <label for="required" class="text-xs text-foreground"
                            >Required</label
                        >
                    </div>
                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            @click="addQuestion"
                            :disabled="newQuestion.processing"
                        >
                            Add question
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="showNewQuestion = false"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>

                <!-- Questions List -->
                <div
                    v-for="question in questionnaire.questions"
                    :key="question.id"
                    class="card-premium rounded-2xl p-5"
                >
                    <div class="mb-3 flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <span v-if="question.emoji" class="text-xl">{{
                                question.emoji
                            }}</span>
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    {{ question.title }}
                                </p>
                                <span
                                    class="text-xs text-muted-foreground capitalize"
                                    >{{ question.type.replace('_', ' ') }} ·
                                    Order {{ question.display_order }}</span
                                >
                            </div>
                        </div>
                        <button
                            @click="deleteQuestion(question.id)"
                            class="text-xs text-destructive hover:text-destructive"
                        >
                            Delete
                        </button>
                    </div>

                    <!-- Options -->
                    <div
                        v-if="
                            ['single_choice', 'multiple_choice'].includes(
                                question.type,
                            )
                        "
                        class="mt-3 space-y-2"
                    >
                        <div
                            v-for="option in question.options"
                            :key="option.id"
                            class="flex items-center gap-2 rounded-xl bg-muted px-3 py-2"
                        >
                            <span v-if="option.emoji" class="text-sm">{{
                                option.emoji
                            }}</span>
                            <span class="flex-1 text-sm text-foreground">{{
                                option.title
                            }}</span>
                            <button
                                @click="duplicateOption(option.id)"
                                class="text-xs text-primary hover:text-primary"
                            >
                                Copy
                            </button>
                            <button
                                @click="deleteOption(option.id)"
                                class="text-xs text-destructive hover:text-destructive"
                                aria-label="Delete option"
                            >
                                <X class="size-4" aria-hidden="true" />
                            </button>
                        </div>

                        <!-- Add Option -->
                        <div
                            v-if="showNewOption === question.id"
                            class="space-y-2 rounded-xl bg-muted p-3"
                        >
                            <div class="grid grid-cols-4 gap-2">
                                <label class="block"
                                    ><span
                                        class="mb-1 block text-xs font-medium text-muted-foreground"
                                        >Emoji</span
                                    ><input
                                        v-model="newOption.emoji"
                                        placeholder="😊"
                                        class="input-premium px-2 py-1.5 text-sm"
                                /></label>
                                <label class="col-span-2 block"
                                    ><span
                                        class="mb-1 block text-xs font-medium text-muted-foreground"
                                        >Option title</span
                                    ><input
                                        v-model="newOption.title"
                                        class="input-premium px-2 py-1.5 text-sm"
                                /></label>
                                <label class="block"
                                    ><span
                                        class="mb-1 block text-xs font-medium text-muted-foreground"
                                        >Value</span
                                    ><input
                                        v-model="newOption.value"
                                        placeholder="value"
                                        class="input-premium px-2 py-1.5 text-sm"
                                /></label>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    @click="addOption(question.id)"
                                    >Add</Button
                                >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="showNewOption = null"
                                    >Cancel</Button
                                >
                            </div>
                        </div>

                        <button
                            v-if="showNewOption !== question.id"
                            @click="showNewOption = question.id"
                            class="inline-flex items-center gap-1 text-xs font-medium text-foreground hover:underline"
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
    </div>
</template>
