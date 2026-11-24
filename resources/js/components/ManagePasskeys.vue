<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import Heading from '@/components/Heading.vue';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

withDefaults(defineProps<Props>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const handleDelete = (id: number, onError: () => void) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
        onError,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <div v-if="canManagePasskeys" class="space-y-5 card-premium p-5">
        <Heading
            variant="small"
            title="Passkeys"
            description="Manage your passkeys for passwordless sign-in"
        />

        <div
            class="divide-y divide-border overflow-hidden rounded-2xl border border-border"
        >
            <template v-if="passkeys.length">
                <PasskeyItem
                    v-for="passkey in passkeys"
                    :key="passkey.id"
                    :passkey="passkey"
                    @remove="handleDelete"
                />
            </template>

            <div
                v-else
                class="flex flex-col items-center gap-3 px-6 py-8 text-center"
            >
                <span
                    class="icon-tile h-14 w-14 text-muted-foreground"
                    aria-hidden="true"
                >
                    <KeyRound class="size-6" />
                </span>
                <div class="space-y-1">
                    <h3 class="text-lg font-semibold text-foreground">
                        No passkeys yet
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Add a passkey to sign in without a password
                    </p>
                </div>
            </div>
        </div>

        <PasskeyRegister @success="handleRegisterSuccess" />
    </div>
</template>
