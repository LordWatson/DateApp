<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import { register } from '@/routes';
</script>

<template>
    <Head title="Invalid invitation" />

    <div class="relative min-h-svh">
        <FloatingHearts />

        <div
            class="relative z-10 mx-auto flex min-h-svh w-full max-w-md flex-col justify-center px-4 py-10"
        >
            <div
                class="flex flex-col items-center gap-5 card-premium p-6 text-center sm:p-8"
            >
                <span class="icon-tile h-16 w-16 text-3xl" aria-hidden="true"
                    >💔</span
                >
                <div class="space-y-2">
                    <p class="eyebrow">Invitation</p>
                    <h1
                        class="text-[1.75rem] leading-[1.15] font-semibold text-foreground"
                    >
                        Invitation not found
                    </h1>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        This invitation link has expired or is no longer valid.
                        Ask your partner to send a new invitation.
                    </p>
                </div>
                <Link :href="register()" class="block">
                    <PrimaryButton full-width>Create an account</PrimaryButton>
                </Link>
            </div>
        </div>
    </div>
</template>
