<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
    emoji?: string;
    step?: number;
    totalSteps?: number;
}

defineProps<Props>();
</script>

<template>
    <div class="animate-page-fade-in space-y-5 card-premium p-6">
        <div v-if="emoji" class="flex justify-center">
            <span class="text-6xl" aria-hidden="true">{{ emoji }}</span>
        </div>

        <div class="space-y-2 text-center">
            <p
                v-if="step && totalSteps"
                class="text-xs font-semibold tracking-widest text-primary uppercase"
            >
                Question {{ step }} of {{ totalSteps }}
            </p>
            <h2 class="text-xl leading-snug font-semibold text-foreground">
                {{ title }}
            </h2>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <slot />
    </div>
</template>
