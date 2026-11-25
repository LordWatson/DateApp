<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import LegalLinks from '@/components/LegalLinks.vue';
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
        <FloatingHearts />

        <div class="relative z-10 w-full max-w-sm">
            <div class="flex flex-col items-center text-center">
                <Link
                    :href="home()"
                    class="flex items-center gap-2.5 rounded-xl focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-4 focus-visible:ring-offset-background focus-visible:outline-none"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl shadow-glow gradient-primary"
                    >
                        <AppLogoIcon
                            class="size-4 text-white"
                            aria-hidden="true"
                        />
                    </div>
                    <span
                        class="font-display text-lg font-semibold tracking-tight text-foreground"
                        >Date Night</span
                    >
                </Link>

                <div class="my-6 hairline-gold w-24" />

                <div v-if="title || description" class="space-y-2">
                    <h1
                        v-if="title"
                        class="text-[1.75rem] leading-[1.15] font-semibold text-balance text-foreground"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-sm leading-relaxed text-pretty text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
            </div>

            <div class="mt-8 card-premium p-6 sm:p-8">
                <slot />
            </div>

            <LegalLinks class="mt-6" />
        </div>
    </div>
</template>
