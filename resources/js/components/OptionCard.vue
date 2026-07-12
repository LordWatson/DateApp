<script setup lang="ts">
interface Props {
    label: string;
    description?: string;
    emoji?: string;
    value: string | number;
    selected?: boolean;
    type?: 'radio' | 'checkbox';
}

const {
    label,
    description,
    emoji,
    value,
    selected = false,
    type = 'radio',
} = defineProps<Props>();

defineEmits<{ select: [value: string | number] }>();
</script>

<template>
    <button
        :role="type"
        :aria-checked="selected"
        :class="[
            'flex w-full items-center gap-4 rounded-2xl border-2 p-4 text-left transition-all duration-200',
            'focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none',
            'hover:border-primary/50 hover:bg-accent',
            selected
                ? 'border-primary bg-accent shadow-md'
                : 'border-border bg-card',
        ]"
        @click="$emit('select', value)"
    >
        <span v-if="emoji" class="shrink-0 text-2xl" aria-hidden="true">{{
            emoji
        }}</span>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-foreground">{{ label }}</p>
            <p v-if="description" class="mt-0.5 text-xs text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <div
            :class="[
                'flex shrink-0 items-center justify-center transition-all duration-200',
                type === 'radio'
                    ? 'h-5 w-5 rounded-full border-2'
                    : 'h-5 w-5 rounded-md border-2',
                selected
                    ? 'border-primary bg-primary'
                    : 'border-muted-foreground/40',
            ]"
            aria-hidden="true"
        >
            <svg
                v-if="selected"
                class="size-3 text-white"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="3"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 13l4 4L19 7"
                />
            </svg>
        </div>
    </button>
</template>
