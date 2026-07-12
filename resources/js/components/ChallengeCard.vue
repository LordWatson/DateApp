<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
    emoji?: string;
    category?: string;
}

defineProps<Props>();

defineEmits<{ refresh: [] }>();
</script>

<template>
    <div class="space-y-4 card-premium p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-foreground">
                Today's Challenge
            </h3>
            <button
                class="rounded-full p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-primary focus:ring-2 focus:ring-primary focus:outline-none"
                aria-label="Get new challenge"
                @click="$emit('refresh')"
            >
                <svg
                    class="size-4"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
            </button>
        </div>

        <div class="flex items-start gap-4">
            <span v-if="emoji" class="shrink-0 text-4xl" aria-hidden="true">{{
                emoji
            }}</span>
            <div class="space-y-1.5">
                <p class="font-semibold text-foreground">{{ title }}</p>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
        </div>

        <div v-if="category" class="flex items-center gap-2">
            <span
                class="inline-flex items-center rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground"
            >
                {{ category }}
            </span>
        </div>
    </div>
</template>
