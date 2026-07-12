<script setup lang="ts">
interface Props {
    modelValue: string | null;
    placeholder?: string;
}

defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <div>
        <input
            type="text"
            :value="modelValue ?? ''"
            :placeholder="placeholder ?? 'Type your answer…'"
            class="w-full rounded-2xl border-2 border-border bg-card px-4 py-4 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none transition-colors duration-200"
            @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
    </div>
</template>
