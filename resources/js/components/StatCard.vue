<script setup lang="ts">
interface Props {
    label: string;
    value: string | number;
    emoji?: string;
    description?: string;
    variant?: 'default' | 'primary' | 'secondary';
}

const {
    label,
    value,
    emoji,
    description,
    variant = 'default',
} = defineProps<Props>();
</script>

<template>
    <div
        :class="[
            'flex flex-col gap-1.5 card-premium p-4',
            variant === 'primary' && 'border-0 text-white gradient-primary',
            variant === 'secondary' && 'border-0 bg-secondary text-white',
        ]"
    >
        <div class="flex items-center justify-between gap-1">
            <span
                :class="[
                    'truncate text-[11px] font-semibold tracking-widest uppercase',
                    variant === 'default'
                        ? 'text-muted-foreground'
                        : 'text-white/80',
                ]"
            >
                {{ label }}
            </span>
            <span v-if="emoji" class="text-base" aria-hidden="true">{{
                emoji
            }}</span>
        </div>
        <div
            :class="[
                'font-display text-3xl leading-none font-semibold tabular-nums',
                variant === 'default' ? 'text-foreground' : 'text-white',
            ]"
        >
            {{ value }}
        </div>
        <p
            v-if="description"
            :class="[
                'text-xs',
                variant === 'default'
                    ? 'text-muted-foreground'
                    : 'text-white/70',
            ]"
        >
            {{ description }}
        </p>
    </div>
</template>
