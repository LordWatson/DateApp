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
    modelValue: string[];
}

defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>();

function toggle(value: string | number, current: string[]): void {
    const str = String(value);
    const updated = current.includes(str)
        ? current.filter((v) => v !== str)
        : [...current, str];
    emit('update:modelValue', updated);
}
</script>

<template>
    <div class="space-y-3" role="group">
        <OptionCard
            v-for="option in options"
            :key="option.id"
            :label="option.title"
            :description="option.description ?? undefined"
            :emoji="option.emoji ?? undefined"
            :value="option.value"
            :selected="modelValue.includes(option.value)"
            type="checkbox"
            @select="(v) => toggle(v, modelValue)"
        />
    </div>
</template>
