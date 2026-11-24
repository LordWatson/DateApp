<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Heart, Lock, Sparkles } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import { login, register } from '@/routes';

const proofPoints = [
    { icon: Heart, title: 'For two', text: 'One shared space' },
    { icon: Lock, title: 'Private', text: 'Only you both' },
    { icon: Sparkles, title: 'Playful', text: 'Never a chore' },
];
</script>

<template>
    <Head title="Date Night — Make every evening special" />

    <div class="relative flex min-h-svh flex-col">
        <FloatingHearts />

        <header
            class="relative z-10 mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-5"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-xl shadow-glow gradient-primary"
                >
                    <AppLogoIcon class="size-4 text-white" aria-hidden="true" />
                </div>
                <span
                    class="font-display text-lg font-semibold tracking-tight text-foreground"
                    >Date Night</span
                >
            </div>

            <nav class="flex items-center gap-1">
                <Link
                    href="/about"
                    class="inline-flex min-h-10 items-center rounded-full px-3 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                >
                    About
                </Link>
                <Link
                    :href="login()"
                    class="inline-flex min-h-10 items-center rounded-full px-3 text-sm font-medium text-foreground transition-colors hover:text-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                >
                    Sign in
                </Link>
            </nav>
        </header>

        <main
            class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 pt-8 pb-16 text-center"
        >
            <div class="animate-page-fade-in w-full max-w-md sm:max-w-lg">
                <p class="eyebrow">For the two of you</p>

                <h1
                    class="mt-5 text-5xl leading-[1.04] font-medium tracking-tight text-balance text-foreground sm:text-6xl"
                >
                    Make every evening
                    <span class="gradient-text italic">special</span>
                </h1>

                <p
                    class="mx-auto mt-6 max-w-sm text-[15px] leading-relaxed text-pretty text-muted-foreground sm:text-base"
                >
                    Discover what makes the perfect date, together. Your own
                    relationship concierge.
                </p>

                <div class="mx-auto mt-10 flex max-w-sm flex-col gap-2">
                    <Link :href="register()" class="block">
                        <PrimaryButton size="lg" full-width
                            >Create account</PrimaryButton
                        >
                    </Link>
                    <Link :href="login()" class="block">
                        <SecondaryButton variant="ghost" full-width
                            >Sign in</SecondaryButton
                        >
                    </Link>
                </div>

                <div class="mx-auto my-10 hairline-gold max-w-xs" />

                <ul class="grid grid-cols-3 gap-3">
                    <li
                        v-for="point in proofPoints"
                        :key="point.title"
                        class="flex flex-col items-center gap-2.5"
                    >
                        <span class="icon-tile h-11 w-11 text-primary">
                            <component
                                :is="point.icon"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </span>
                        <span class="space-y-0.5">
                            <span
                                class="block text-sm font-medium text-foreground"
                                >{{ point.title }}</span
                            >
                            <span class="block text-xs text-muted-foreground">{{
                                point.text
                            }}</span>
                        </span>
                    </li>
                </ul>
            </div>
        </main>
    </div>
</template>
