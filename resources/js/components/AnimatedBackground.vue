<script setup lang="ts">
interface Props {
    variant?: 'hearts' | 'gradient' | 'minimal';
}

const { variant = 'hearts' } = defineProps<Props>();
</script>

<template>
    <div
        class="pointer-events-none fixed inset-0 -z-10 overflow-hidden"
        aria-hidden="true"
    >
        <div
            v-if="variant === 'gradient' || variant === 'hearts'"
            class="absolute inset-0 bg-gradient-to-br from-pink-50 via-background to-purple-50 dark:from-pink-950/20 dark:via-background dark:to-purple-950/20"
        />
        <div
            v-if="variant === 'hearts'"
            class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-primary/5 blur-3xl"
        />
        <div
            v-if="variant === 'hearts'"
            class="absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-secondary/5 blur-3xl"
        />
        <div
            v-if="variant === 'minimal'"
            class="absolute inset-0 bg-background"
        />
    </div>
</template>
