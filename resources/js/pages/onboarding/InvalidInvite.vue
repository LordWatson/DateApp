<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import { register } from '@/routes';
</script>

<template>
    <Head title="Invalid Invitation" />

    <div class="relative min-h-svh bg-background">
        <FloatingHearts />

        <div class="relative z-10 flex min-h-svh flex-col items-center justify-center px-4 py-8 text-center">
            <div class="w-full max-w-md">
                <div class="card-premium p-8">
                    <div class="mb-6 text-6xl">💔</div>
                    <h1 class="mb-3 text-2xl font-semibold text-foreground">Invitation Not Found</h1>
                    <p class="mb-8 text-muted-foreground">
                        This invitation link has expired or is no longer valid. Ask your partner to send a new invitation.
                    </p>
                    <PrimaryButton full-width :href="register()">
                        Create an Account
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </div>
</template>
