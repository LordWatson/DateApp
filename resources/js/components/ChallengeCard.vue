<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
    emoji?: string;
    category?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="space-y-4 card-premium p-6">
        <p class="eyebrow">Today's challenge</p>

        <div class="flex items-start gap-4">
            <span
                v-if="emoji"
                class="icon-tile h-14 w-14 text-3xl"
                aria-hidden="true"
                >{{ emoji }}</span
            >
            <div class="space-y-1.5">
                <h3 class="text-xl leading-snug font-semibold text-foreground">
                    {{ title }}
                </h3>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
        </div>

        <div v-if="category" class="flex items-center gap-2">
            <span class="badge-soft capitalize">
                {{ category }}
            </span>
        </div>
    </div>
</template>
