<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AnimatedBackground from '@/components/AnimatedBackground.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
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
                            Discover what makes the perfect date — together.
                            <br />
                            Your new relationship concierge.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <Link :href="login()" class="block">
                        <PrimaryButton size="lg" full-width>Sign in</PrimaryButton>
                    </Link>
                    <Link :href="register()" class="block">
                        <SecondaryButton size="lg" full-width>Create account</SecondaryButton>
                    </Link>
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
