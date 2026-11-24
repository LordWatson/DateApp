<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? 'space-y-1' : 'mb-8 space-y-1.5'">
        <h2
            :class="[
                'leading-tight font-semibold text-foreground',
                variant === 'small' ? 'text-lg' : 'text-[1.75rem]',
            ]"
        >
            {{ title }}
        </h2>
        <p
            v-if="description"
            class="text-sm leading-relaxed text-muted-foreground"
        >
            {{ description }}
        </p>
    </header>
</template>
