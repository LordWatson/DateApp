<script setup lang="ts">
import OptionCard from '@/components/OptionCard.vue';

interface Option {
    id: number;
    title: string;
    description?: string | null;
    emoji?: string | null;
    value: string;
}

interface Props {
    options: Option[];
    modelValue: string | null;
}

defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

function select(value: string | number): void {
    emit('update:modelValue', String(value));
}
</script>

<template>
    <div class="space-y-3" role="radiogroup">
        <OptionCard
            v-for="option in options"
            :key="option.id"
            :label="option.title"
            :description="option.description ?? undefined"
            :emoji="option.emoji ?? undefined"
            :value="option.value"
            :selected="modelValue === option.value"
            type="radio"
            @select="select"
        />
    </div>
</template>
