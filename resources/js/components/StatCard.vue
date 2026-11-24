<script setup lang="ts">
interface Props {
    label: string;
    value: string | number;
    emoji?: string;
    description?: string;
    variant?: 'default' | 'primary' | 'secondary';
}

const { label, value, description, variant = 'default' } = defineProps<Props>();
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
                    'truncate eyebrow',
                    variant !== 'default' && '!text-white/80',
                ]"
            >
                {{ label }}
            </span>
        </div>
        <div
            :class="[
                'font-display text-4xl leading-none font-medium tabular-nums',
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
