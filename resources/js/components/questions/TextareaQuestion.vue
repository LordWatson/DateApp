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
            class="input-premium resize-none"
            @input="onInput"
        />
        <div class="flex justify-end">
            <span
                :class="[
                    'text-xs tabular-nums',
                    charCount >= MAX_CHARS
                        ? 'text-destructive'
                        : 'text-muted-foreground',
                ]"
            >
                {{ charCount }} / {{ MAX_CHARS }}
            </span>
        </div>
    </div>
</template>
