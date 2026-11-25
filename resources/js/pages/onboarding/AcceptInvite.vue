<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';

const props = defineProps<{
    token: string;
    sender: { name: string; avatar: string | null };
}>();

const working = ref<'accept' | 'decline' | null>(null);

function respond(action: 'accept' | 'decline'): void {
    working.value = action;
    router.post(
        `/invite/${props.token}/${action}`,
        {},
        {
            onFinish: () => {
                working.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Partner invitation" />

    <div class="relative min-h-svh">
        <FloatingHearts />

        <div
            class="relative z-10 mx-auto flex min-h-svh w-full max-w-md flex-col justify-center px-4 py-10"
        >
            <div
                class="flex flex-col items-center gap-5 card-premium p-6 text-center sm:p-8"
            >
                <span class="icon-tile h-16 w-16 text-3xl" aria-hidden="true">{{
                    sender.avatar || '💞'
                }}</span>
                <div class="space-y-2">
                    <p class="eyebrow">Invitation</p>
                    <h1
                        class="text-[1.75rem] leading-[1.15] font-semibold text-foreground"
                    >
                        Connect with {{ sender.name }}?
                    </h1>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ sender.name }} invited you to be their partner on
                        Date Night. Once connected, you will share date night
                        plans, moments, love notes and your calendar with each
                        other.
                    </p>
                </div>
                <div class="w-full space-y-3">
                    <PrimaryButton
                        full-width
                        :loading="working === 'accept'"
                        :disabled="working !== null"
                        @click="respond('accept')"
                    >
                        Connect
                    </PrimaryButton>
                    <SecondaryButton
                        variant="ghost"
                        full-width
                        :loading="working === 'decline'"
                        :disabled="working !== null"
                        @click="respond('decline')"
                    >
                        No thanks
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </div>
</template>
