<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const MAX_CHARS = 500;

interface Props {
    modelValue: string | null;
    placeholder?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const textareaRef = ref<HTMLTextAreaElement | null>(null);
const charCount = computed(() => (props.modelValue ?? '').length);

function onInput(event: Event): void {
    const target = event.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
    autoGrow(target);
}

function autoGrow(el: HTMLTextAreaElement): void {
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight}px`;
}

watch(
    () => props.modelValue,
    () => {
        if (textareaRef.value) {
            autoGrow(textareaRef.value);
        }
    },
);
</script>

<template>
    <div class="space-y-2">
        <textarea
            ref="textareaRef"
            :value="modelValue ?? ''"
            :placeholder="placeholder ?? 'Share your thoughts…'"
            :maxlength="MAX_CHARS"
            rows="3"
            class="w-full resize-none rounded-2xl border-2 border-border bg-card px-4 py-4 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none transition-colors duration-200"
            @input="onInput"
        />
        <div class="flex justify-end">
            <span
                :class="[
                    'text-xs',
                    charCount >= MAX_CHARS ? 'text-destructive' : 'text-muted-foreground',
                ]"
            >
                {{ charCount }} / {{ MAX_CHARS }}
            </span>
        </div>
    </div>
</template>
