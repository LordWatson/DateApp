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
            'flex card-hover flex-col gap-3 card-premium p-6',
            variant === 'primary' && 'border-0 text-white gradient-primary',
            variant === 'secondary' && 'border-0 bg-secondary text-white',
        ]"
    >
        <div class="flex items-center justify-between">
            <span
                :class="[
                    'text-sm font-medium',
                    variant === 'default'
                        ? 'text-muted-foreground'
                        : 'text-white/80',
                ]"
            >
                {{ label }}
            </span>
            <span v-if="emoji" class="text-2xl" aria-hidden="true">{{
                emoji
            }}</span>
        </div>
        <div
            :class="[
                'text-3xl font-semibold',
                variant === 'default' ? 'text-foreground' : 'text-white',
            ]"
        >
            {{ value }}
        </div>
        <p
            v-if="description"
            :class="[
                'text-sm',
                variant === 'default'
                    ? 'text-muted-foreground'
                    : 'text-white/70',
            ]"
        >
            {{ description }}
        </p>
    </div>
</template>
