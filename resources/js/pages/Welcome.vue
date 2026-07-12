<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AnimatedBackground from '@/components/AnimatedBackground.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import { login, register } from '@/routes';

</script>

<template>
    <Head title="Date Night — Make every evening special" />

    <div class="relative flex min-h-svh flex-col bg-background">
        <AnimatedBackground variant="hearts" />
        <FloatingHearts />

        <header
            class="relative z-10 flex items-center justify-between px-6 py-5"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-2xl shadow-md gradient-primary"
                >
                    <AppLogoIcon class="size-5 text-white" />
                </div>
                <span class="gradient-text font-semibold">Date Night</span>
            </div>

            <nav class="flex items-center gap-3">
                <Link
                    :href="login()"
                    class="rounded-lg px-2 py-1 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus:ring-2 focus:ring-primary focus:outline-none"
                >
                    Sign in
                </Link>
                <Link :href="register()">
                    <SecondaryButton size="sm" variant="outline"
                        >Get started</SecondaryButton
                    >
                </Link>
            </nav>
        </header>

        <main
            class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 py-12 text-center"
        >
            <div class="animate-page-fade-in max-w-sm space-y-8">
                <div class="space-y-4">
                    <div class="flex justify-center">
                        <div
                            class="animate-pulse-glow flex h-24 w-24 items-center justify-center rounded-3xl shadow-2xl gradient-primary"
                        >
                            <AppLogoIcon class="size-14 text-white" />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h1
                            class="text-4xl leading-tight font-semibold text-foreground"
                        >
                            Make every evening
                            <span class="gradient-text">special</span>
                        </h1>
                        <p class="leading-relaxed text-muted-foreground">
                            A playful questionnaire for couples to discover what
                            makes the perfect date night — together.
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center justify-center gap-6 text-sm text-muted-foreground"
                >
                    <div class="flex items-center gap-1.5">
                        <span>💕</span>
                        <span>For couples</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>🔒</span>
                        <span>Private</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>✨</span>
                        <span>Fun</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
