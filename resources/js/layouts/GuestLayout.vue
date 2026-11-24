<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AnimatedBackground from '@/components/AnimatedBackground.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center px-4 py-12"
    >
        <AnimatedBackground variant="hearts" />
        <FloatingHearts />

        <div class="relative z-10 w-full max-w-sm space-y-8">
            <div class="flex flex-col items-center gap-4">
                <Link
                    :href="home()"
                    class="flex flex-col items-center gap-3 rounded-2xl p-2 focus:ring-2 focus:ring-primary focus:ring-offset-4 focus:outline-none"
                >
                    <div
                        class="animate-pulse-glow flex h-16 w-16 items-center justify-center rounded-2xl shadow-glow gradient-primary"
                    >
                        <AppLogoIcon class="size-9 text-white" />
                    </div>
                    <span
                        class="font-display text-3xl font-semibold tracking-tight text-foreground"
                        >Date Night</span
                    >
                </Link>

                <div
                    v-if="title || description"
                    class="space-y-1.5 text-center"
                >
                    <h1
                        v-if="title"
                        class="text-2xl font-semibold text-foreground"
                    >
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
            </div>

            <div class="card-premium p-6 sm:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
