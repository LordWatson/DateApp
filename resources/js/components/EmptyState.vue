<script setup lang="ts">
interface Props {
    emoji?: string;
    title: string;
    description?: string;
    actionLabel?: string;
}

const { emoji = '✨', title, description, actionLabel } = defineProps<Props>();

defineEmits<{ action: [] }>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-4 px-6 py-10 text-center"
    >
        <div
            class="flex h-16 w-16 items-center justify-center rounded-2xl text-3xl ring-1 ring-border gradient-primary-soft"
        >
            {{ emoji }}
        </div>

        <div class="max-w-xs space-y-2">
            <h3 class="text-lg font-semibold text-foreground">{{ title }}</h3>
            <p
                v-if="description"
                class="text-sm leading-relaxed text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <button v-if="actionLabel" class="btn-primary" @click="$emit('action')">
            {{ actionLabel }}
        </button>

        <slot />
    </div>
</template>
