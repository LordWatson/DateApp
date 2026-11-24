<script setup lang="ts">
import { Check } from '@lucide/vue';

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
            'flex min-h-16 w-full items-center gap-3.5 rounded-2xl border px-4 py-3 text-left transition-all duration-200',
            'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none',
            'active:scale-[0.99]',
            selected
                ? 'border-primary bg-accent'
                : 'border-border bg-card hover:border-primary/40 hover:bg-muted/60',
        ]"
        @click="$emit('select', value)"
    >
        <span
            v-if="emoji"
            class="icon-tile h-11 w-11 text-xl"
            aria-hidden="true"
            >{{ emoji }}</span
        >

        <div class="min-w-0 flex-1">
            <p class="text-[15px] leading-snug font-medium text-foreground">
                {{ label }}
            </p>
            <p
                v-if="description"
                class="mt-0.5 text-[13px] leading-snug text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <div
            :class="[
                'flex size-5 shrink-0 items-center justify-center border-2 transition-colors duration-200',
                type === 'radio' ? 'rounded-full' : 'rounded-md',
                selected
                    ? 'border-primary bg-primary text-white'
                    : 'border-muted-foreground/40',
            ]"
            aria-hidden="true"
        >
            <Check v-if="selected" class="size-3" :stroke-width="3.5" />
        </div>
    </button>
</template>
