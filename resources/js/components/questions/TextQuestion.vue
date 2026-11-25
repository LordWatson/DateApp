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
            maxlength="255"
            :value="modelValue ?? ''"
            :placeholder="placeholder ?? 'Type your answer…'"
            class="input-premium"
            @input="
                emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value,
                )
            "
        />
    </div>
</template>
