<script setup lang="ts" generic="T extends string | number">
interface Option {
    key: T;
    label: string;
    count?: number;
}

defineProps<{ options: Option[] }>();

const model = defineModel<T>({ required: true });
</script>

<template>
    <div
        class="flex rounded-full border border-border bg-muted/70 p-1"
        role="tablist"
    >
        <button
            v-for="option in options"
            :key="option.key"
            type="button"
            role="tab"
            :aria-selected="model === option.key"
            :class="[
                'flex min-h-9 flex-1 items-center justify-center gap-1.5 rounded-full px-3 text-[13px] font-medium transition-all duration-200',
                model === option.key
                    ? 'bg-card text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
            ]"
            @click="model = option.key"
        >
            {{ option.label }}
            <span
                v-if="option.count"
                class="rounded-full bg-primary px-1.5 text-[10px] leading-4 font-semibold text-white"
                >{{ option.count }}</span
            >
        </button>
    </div>
</template>
