<script setup lang="ts">
interface Props {
    size?: 'sm' | 'md' | 'lg';
    label?: string;
}

const { size = 'md', label = 'Loading…' } = defineProps<Props>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3"
        role="status"
        :aria-label="label"
    >
        <div
            :class="[
                'animate-spin rounded-full border-4 border-muted border-t-primary',
                size === 'sm' && 'size-6',
                size === 'md' && 'size-10',
                size === 'lg' && 'size-16',
            ]"
        />
        <p v-if="label" class="text-sm font-medium text-muted-foreground">
            {{ label }}
        </p>
    </div>
</template>
