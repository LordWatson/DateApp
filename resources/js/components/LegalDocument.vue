<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LegalLinks from '@/components/LegalLinks.vue';
import { home } from '@/routes';

defineProps<{
    title: string;
    updated: string;
}>();
</script>

<template>
    <Head :title="title" />

    <div class="mx-auto flex min-h-svh w-full max-w-2xl flex-col px-6 py-5">
        <header>
            <Link
                :href="home()"
                class="inline-flex items-center gap-2.5 rounded-xl focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-4 focus-visible:ring-offset-background focus-visible:outline-none"
            >
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-xl shadow-glow gradient-primary"
                >
                    <AppLogoIcon class="size-4 text-white" aria-hidden="true" />
                </span>
                <span
                    class="font-display text-lg font-semibold tracking-tight text-foreground"
                    >Date Night</span
                >
            </Link>
        </header>

        <main class="flex-1 pt-10 pb-12">
            <p
                role="note"
                class="rounded-2xl border border-warning/40 bg-warning/10 px-4 py-3 text-sm leading-relaxed text-foreground"
            >
                <strong>Draft.</strong> This text describes how the app works
                today but has not been reviewed by a lawyer. The site owner must
                have it reviewed before launch.
            </p>

            <h1
                class="mt-8 text-4xl leading-[1.1] font-medium tracking-tight text-foreground"
            >
                {{ title }}
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Last updated {{ updated }}
            </p>

            <div
                class="mt-8 space-y-4 text-[15px] leading-relaxed text-muted-foreground [&_a]:font-medium [&_a]:text-primary [&_a]:underline [&_a]:underline-offset-4 [&_h2]:pt-6 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-foreground [&_strong]:text-foreground [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-5"
            >
                <slot />
            </div>
        </main>

        <LegalLinks />
    </div>
</template>
