<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useId } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

export interface EditableQuestion {
    id: number;
    title: string;
    description: string | null;
    emoji: string | null;
    type: string;
    display_order: number;
    required: boolean;
    minimum_value: number | null;
    maximum_value: number | null;
    step_value: number | null;
    unit: string | null;
}

// Without `question` the form creates one on `questionnaireId`.
const props = defineProps<{
    questionnaireId: number;
    question?: EditableQuestion;
    nextOrder: number;
}>();

const emit = defineEmits<{ done: [] }>();

const uid = useId();

const form = useForm({
    title: props.question?.title ?? '',
    description: props.question?.description ?? '',
    emoji: props.question?.emoji ?? '',
    type: props.question?.type ?? 'single_choice',
    display_order: props.question?.display_order ?? props.nextOrder,
    required: props.question?.required ?? true,
    minimum_value: props.question?.minimum_value ?? null,
    maximum_value: props.question?.maximum_value ?? null,
    step_value: props.question?.step_value ?? null,
    unit: props.question?.unit ?? '',
});

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => emit('done') };

    if (props.question) {
        form.put(admin.questions.update(props.question.id).url, options);
    } else {
        form.post(admin.questions.store(props.questionnaireId).url, options);
    }
};
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <div class="grid grid-cols-4 gap-3">
            <div>
                <label
                    :for="`${uid}-emoji`"
                    class="text-xs text-muted-foreground"
                    >Emoji</label
                >
                <input
                    :id="`${uid}-emoji`"
                    v-model="form.emoji"
                    class="mt-1 input-premium px-3 py-2 text-sm"
                />
                <InputError :message="form.errors.emoji" />
            </div>
            <div class="col-span-3">
                <label
                    :for="`${uid}-title`"
                    class="text-xs text-muted-foreground"
                    >Title</label
                >
                <input
                    :id="`${uid}-title`"
                    v-model="form.title"
                    required
                    class="mt-1 input-premium px-3 py-2 text-sm"
                />
                <InputError :message="form.errors.title" />
            </div>
        </div>

        <div>
            <label
                :for="`${uid}-description`"
                class="text-xs text-muted-foreground"
                >Description</label
            >
            <textarea
                :id="`${uid}-description`"
                v-model="form.description"
                rows="2"
                class="mt-1 input-premium px-3 py-2 text-sm"
            />
        </div>

        <div>
            <label :for="`${uid}-type`" class="text-xs text-muted-foreground"
                >Type</label
            >
            <select
                :id="`${uid}-type`"
                v-model="form.type"
                class="mt-1 input-premium px-3 py-2 text-sm"
            >
                <option value="single_choice">Single choice</option>
                <option value="multiple_choice">Multiple choice</option>
                <option value="slider">Slider</option>
                <option value="text">Text</option>
                <option value="textarea">Textarea</option>
            </select>
            <InputError :message="form.errors.type" />
        </div>

        <div v-if="form.type === 'slider'" class="grid grid-cols-4 gap-3">
            <div>
                <label :for="`${uid}-min`" class="text-xs text-muted-foreground"
                    >Minimum</label
                >
                <input
                    :id="`${uid}-min`"
                    v-model.number="form.minimum_value"
                    type="number"
                    class="mt-1 input-premium px-3 py-2 text-sm"
                />
            </div>
            <div>
                <label :for="`${uid}-max`" class="text-xs text-muted-foreground"
                    >Maximum</label
                >
                <input
                    :id="`${uid}-max`"
                    v-model.number="form.maximum_value"
                    type="number"
                    class="mt-1 input-premium px-3 py-2 text-sm"
                />
            </div>
            <div>
                <label
                    :for="`${uid}-step`"
                    class="text-xs text-muted-foreground"
                    >Step</label
                >
                <input
                    :id="`${uid}-step`"
                    v-model.number="form.step_value"
                    type="number"
                    min="1"
                    class="mt-1 input-premium px-3 py-2 text-sm"
                />
            </div>
            <div>
                <label
                    :for="`${uid}-unit`"
                    class="text-xs text-muted-foreground"
                    >Unit</label
                >
                <input
                    :id="`${uid}-unit`"
                    v-model="form.unit"
                    maxlength="20"
                    placeholder="min"
                    class="mt-1 input-premium px-3 py-2 text-sm"
                />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input
                :id="`${uid}-required`"
                v-model="form.required"
                type="checkbox"
                class="size-4 rounded accent-primary"
            />
            <label :for="`${uid}-required`" class="text-xs text-foreground"
                >Required</label
            >
        </div>

        <div class="flex gap-2">
            <Button size="sm" type="submit" :disabled="form.processing">
                {{ question ? 'Save question' : 'Add question' }}
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="emit('done')"
            >
                Cancel
            </Button>
        </div>
    </form>
</template>
