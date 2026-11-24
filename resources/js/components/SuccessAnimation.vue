<script setup lang="ts">
import { onMounted, ref } from 'vue';

interface Props {
    title?: string;
    message?: string;
    emoji?: string;
}

const { title = 'Done!', message = '', emoji = '🎉' } = defineProps<Props>();

const confetti = ref<
    Array<{
        id: number;
        x: number;
        color: string;
        delay: number;
        duration: number;
    }>
>([]);

const colors = [
    'var(--primary)',
    'var(--gold)',
    'var(--gold-soft)',
    'var(--success)',
];

onMounted(() => {
    confetti.value = Array.from({ length: 30 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        color: colors[Math.floor(Math.random() * colors.length)],
        delay: Math.random() * 1.5,
        duration: Math.random() * 2 + 2,
    }));
});
</script>

<template>
    <div
        class="relative flex flex-col items-center justify-center gap-6 py-12 text-center"
    >
        <div
            v-for="piece in confetti"
            :key="piece.id"
            class="pointer-events-none absolute top-0 h-2 w-2 rounded-sm"
            :style="{
                left: `${piece.x}%`,
                backgroundColor: piece.color,
                animation: `confetti-fall ${piece.duration}s ease-in ${piece.delay}s both`,
            }"
            aria-hidden="true"
        />

        <div
            class="animate-success-pop icon-tile h-16 w-16 text-3xl"
            aria-hidden="true"
        >
            {{ emoji }}
        </div>

        <div class="space-y-2">
            <h2
                class="text-[1.75rem] leading-tight font-semibold text-foreground"
            >
                {{ title }}
            </h2>
            <p
                v-if="message"
                class="mx-auto max-w-xs text-sm leading-relaxed text-muted-foreground"
            >
                {{ message }}
            </p>
        </div>

        <slot />
    </div>
</template>
