<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

interface Heart {
    id: number;
    x: number;
    size: number;
    duration: number;
    delay: number;
    opacity: number;
}

const hearts = ref<Heart[]>([]);
let nextId = 0;

function createHeart(): Heart {
    return {
        id: nextId++,
        x: Math.random() * 100,
        size: Math.random() * 16 + 10,
        duration: Math.random() * 10 + 8,
        delay: Math.random() * 5,
        opacity: Math.random() * 0.3 + 0.1,
    };
}

let interval: ReturnType<typeof setInterval>;

onMounted(() => {
    for (let i = 0; i < 12; i++) {
        hearts.value.push(createHeart());
    }

    interval = setInterval(() => {
        if (hearts.value.length < 18) {
            hearts.value.push(createHeart());
        }
    }, 2000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <div
        class="pointer-events-none fixed inset-0 overflow-hidden"
        aria-hidden="true"
    >
        <div
            v-for="heart in hearts"
            :key="heart.id"
            class="animate-float-heart absolute text-primary"
            :style="{
                left: `${heart.x}%`,
                bottom: '-20px',
                fontSize: `${heart.size}px`,
                animationDuration: `${heart.duration}s`,
                animationDelay: `${heart.delay}s`,
                opacity: heart.opacity,
            }"
        >
            ♥
        </div>
    </div>
</template>
