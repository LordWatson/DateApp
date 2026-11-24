<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, RefreshCw } from '@lucide/vue';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import AlertError from '@/components/AlertError.vue';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <div class="w-full space-y-4 rounded-2xl border border-border p-4">
        <div class="flex items-start gap-3.5">
            <span class="icon-tile h-11 w-11 text-primary" aria-hidden="true">
                <LockKeyhole class="size-5" />
            </span>
            <div class="min-w-0 space-y-1">
                <h3 class="text-lg leading-tight font-semibold text-foreground">
                    2FA recovery codes
                </h3>
                <p class="text-sm leading-relaxed text-muted-foreground">
                    Recovery codes let you regain access if you lose your 2FA
                    device. Store them in a secure password manager.
                </p>
            </div>
        </div>
        <div>
            <div
                class="flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between"
            >
                <Button
                    variant="outline"
                    @click="toggleRecoveryCodesVisibility"
                    class="w-fit"
                >
                    <component
                        :is="isRecoveryCodesVisible ? EyeOff : Eye"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ isRecoveryCodesVisible ? 'Hide' : 'View' }} recovery
                    codes
                </Button>

                <Form
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    v-bind="regenerateRecoveryCodes.form()"
                    method="post"
                    :options="{ preserveScroll: true }"
                    @success="fetchRecoveryCodes"
                    #default="{ processing }"
                >
                    <Button
                        variant="secondary"
                        type="submit"
                        :disabled="processing"
                    >
                        <RefreshCw aria-hidden="true" /> Regenerate codes
                    </Button>
                </Form>
            </div>
            <div
                :class="[
                    'relative overflow-hidden transition-all duration-300',
                    isRecoveryCodesVisible
                        ? 'h-auto opacity-100'
                        : 'h-0 opacity-0',
                ]"
            >
                <div v-if="errors?.length" class="mt-4">
                    <AlertError :errors="errors" />
                </div>
                <div v-else class="mt-3 space-y-3">
                    <div
                        ref="recoveryCodeSectionRef"
                        class="grid gap-1.5 rounded-2xl bg-muted p-4 font-mono text-sm text-foreground"
                    >
                        <div v-if="!recoveryCodesList.length" class="space-y-2">
                            <div
                                v-for="n in 8"
                                :key="n"
                                class="h-4 animate-pulse rounded bg-muted-foreground/20"
                            ></div>
                        </div>
                        <div
                            v-else
                            v-for="(code, index) in recoveryCodesList"
                            :key="index"
                        >
                            {{ code }}
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground select-none">
                        Each recovery code can be used once to access your
                        account and will be removed after use. If you need more,
                        click
                        <span class="font-medium text-foreground"
                            >Regenerate codes</span
                        >
                        above.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
