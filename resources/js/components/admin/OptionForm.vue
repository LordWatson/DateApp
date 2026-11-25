<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

export interface EditableOption {
    id: number;
    title: string;
    description: string | null;
    emoji: string | null;
    value: string;
    signals: string[] | null;
    display_order: number;
}

// Without `option` the form creates one on `questionId`.
const props = defineProps<{
    questionId: number;
    option?: EditableOption;
    nextOrder: number;
    signalOptions: string[];
}>();

const emit = defineEmits<{ done: [] }>();

const form = useForm({
    title: props.option?.title ?? '',
    description: props.option?.description ?? '',
    emoji: props.option?.emoji ?? '',
    value: props.option?.value ?? '',
    signals: [...(props.option?.signals ?? [])],
    display_order: props.option?.display_order ?? props.nextOrder,
});

const toggleSignal = (signal: string) => {
    form.signals = form.signals.includes(signal)
        ? form.signals.filter((s) => s !== signal)
        : [...form.signals, signal];
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => emit('done') };

    if (props.option) {
        form.put(admin.options.update(props.option.id).url, options);
    } else {
        form.post(admin.options.store(props.questionId).url, options);
    }
};
</script>

<template>
    <form class="space-y-2 rounded-xl bg-muted p-3" @submit.prevent="submit">
        <div class="grid grid-cols-4 gap-2">
            <label class="block"
                ><span
                    class="mb-1 block text-xs font-medium text-muted-foreground"
                    >Emoji</span
                ><input
                    v-model="form.emoji"
                    class="input-premium px-2 py-1.5 text-sm"
            /></label>
            <label class="col-span-2 block"
                ><span
                    class="mb-1 block text-xs font-medium text-muted-foreground"
                    >Option title</span
                ><input
                    v-model="form.title"
                    required
                    class="input-premium px-2 py-1.5 text-sm"
            /></label>
            <label class="block"
                ><span
                    class="mb-1 block text-xs font-medium text-muted-foreground"
                    >Value</span
                ><input
                    v-model="form.value"
                    required
                    class="input-premium px-2 py-1.5 text-sm"
            /></label>
        </div>
        <InputError
            :message="
                form.errors.title ?? form.errors.value ?? form.errors.emoji
            "
        />

        <fieldset>
            <legend class="mb-1 text-xs font-medium text-muted-foreground">
                Plan signals
            </legend>
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="signal in signalOptions"
                    :key="signal"
                    type="button"
                    class="chip"
                    :aria-pressed="form.signals.includes(signal)"
                    @click="toggleSignal(signal)"
                >
                    {{ signal }}
                </button>
            </div>
            <p class="mt-1 text-xs text-muted-foreground">
                What choosing this option says about the date the person wants.
                The plan generator scores on these.
            </p>
            <InputError :message="form.errors.signals" />
        </fieldset>

        <div class="flex gap-2">
            <Button size="sm" type="submit" :disabled="form.processing">
                {{ option ? 'Save option' : 'Add option' }}
            </Button>
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="emit('done')"
                >Cancel</Button
            >
        </div>
    </form>
</template>
