<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import PartnerController from '@/actions/App/Http/Controllers/PartnerController';
import EmptyState from '@/components/EmptyState.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import type { Auth } from '@/types';

defineOptions({ layout: MobileLayout });

interface Partner {
    id: number;
    name: string;
    display_name?: string;
    avatar?: string;
    created_at: string;
}

interface PendingInvitation {
    email: string;
    expires_at: string;
    token: string;
}

interface Props {
    partner: Partner | null;
    pendingInvitation: PendingInvitation | null;
    connectedSince: string | null;
}

defineProps<Props>();
const page = usePage<{ auth: Auth; flash?: { success?: string; error?: string } }>();

const showDisconnectConfirm = ref(false);
const inviteForm = useForm({ email: '' });
const disconnectForm = useForm({});
const resendForm = useForm({});

const flash = page.props.flash;

function sendInvite(): void {
    inviteForm.post(PartnerController.sendInvite().url, {
        onSuccess: () => {
 inviteForm.reset();
},
    });
}

function resendInvite(): void {
    resendForm.post(PartnerController.resendInvite().url);
}

function disconnect(): void {
    disconnectForm.delete(PartnerController.disconnect().url, {
        onSuccess: () => {
 showDisconnectConfirm.value = false;
},
    });
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Partner" />

    <div class="space-y-4 px-4 py-6 pb-24">
        <div class="mb-2">
            <h1 class="text-2xl font-semibold text-foreground">Partner 💑</h1>
            <p class="text-sm text-muted-foreground">Manage your connection</p>
        </div>

        <!-- Flash Messages -->
        <div v-if="flash?.success" class="rounded-2xl bg-success/10 p-4 text-sm font-medium text-success">
            ✓ {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="rounded-2xl bg-destructive/10 p-4 text-sm font-medium text-destructive">
            {{ flash.error }}
        </div>

        <!-- Connected Partner -->
        <div v-if="partner" class="space-y-4">
            <div class="card-premium p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-primary/10 text-4xl shadow-inner">
                        {{ partner.avatar || '💕' }}
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-foreground">
                            {{ partner.display_name ?? partner.name }}
                        </h2>
                        <p class="text-sm text-success">● Connected</p>
                        <p v-if="connectedSince" class="mt-1 text-xs text-muted-foreground">
                            Since {{ formatDate(connectedSince) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Disconnect -->
            <div class="card-premium p-6">
                <h3 class="mb-3 font-semibold text-foreground">Disconnect Partner</h3>
                <p class="mb-4 text-sm text-muted-foreground">
                    This will remove your connection. You can reconnect at any time by sending a new invitation.
                </p>

                <div v-if="!showDisconnectConfirm">
                    <SecondaryButton
                        full-width
                        class="!border-destructive !text-destructive hover:!bg-destructive/10"
                        @click="showDisconnectConfirm = true"
                    >
                        Disconnect Partner
                    </SecondaryButton>
                </div>

                <div v-else class="space-y-3">
                    <p class="rounded-2xl bg-destructive/10 p-3 text-sm font-medium text-destructive">
                        ⚠️ Are you sure? This will disconnect both of you.
                    </p>
                    <div class="flex gap-3">
                        <SecondaryButton class="flex-1" @click="showDisconnectConfirm = false">
                            Cancel
                        </SecondaryButton>
                        <button
                            class="flex-1 rounded-2xl bg-destructive py-3 text-sm font-semibold text-white transition-all hover:bg-destructive/90 active:scale-[0.98] disabled:opacity-50"
                            :disabled="disconnectForm.processing"
                            @click="disconnect"
                        >
                            {{ disconnectForm.processing ? 'Disconnecting...' : 'Yes, Disconnect' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Invitation -->
        <div v-else-if="pendingInvitation" class="space-y-4">
            <div class="card-premium p-6">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-warning/10 text-2xl">
                        ⏳
                    </div>
                    <div>
                        <h2 class="font-semibold text-foreground">Invitation Pending</h2>
                        <p class="text-sm text-muted-foreground">Sent to {{ pendingInvitation.email }}</p>
                    </div>
                </div>
                <p class="mb-4 text-sm text-muted-foreground">
                    Expires {{ formatDate(pendingInvitation.expires_at) }}
                </p>
                <PrimaryButton full-width :disabled="resendForm.processing" @click="resendInvite">
                    {{ resendForm.processing ? 'Sending...' : 'Resend Invitation 💌' }}
                </PrimaryButton>
            </div>

            <!-- Send new invite -->
            <div class="card-premium p-6">
                <h3 class="mb-3 font-semibold text-foreground">Invite Someone Else</h3>
                <form class="space-y-3" @submit.prevent="sendInvite">
                    <input
                        v-model="inviteForm.email"
                        type="email"
                        placeholder="partner@email.com"
                        class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        required
                    />
                    <p v-if="inviteForm.errors.email" class="text-sm text-destructive">{{ inviteForm.errors.email }}</p>
                    <PrimaryButton type="submit" full-width :disabled="inviteForm.processing">
                        {{ inviteForm.processing ? 'Sending...' : 'Send New Invitation ❤️' }}
                    </PrimaryButton>
                </form>
            </div>
        </div>

        <!-- No Partner -->
        <div v-else class="card-premium p-6">
            <EmptyState
                emoji="💌"
                title="Invite your partner"
                description="Invite your partner to begin your journey together."
            />
            <form class="mt-6 space-y-3" @submit.prevent="sendInvite">
                <input
                    v-model="inviteForm.email"
                    type="email"
                    placeholder="partner@email.com"
                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    required
                />
                <p v-if="inviteForm.errors.email" class="text-sm text-destructive">{{ inviteForm.errors.email }}</p>
                <PrimaryButton type="submit" full-width :disabled="inviteForm.processing">
                    {{ inviteForm.processing ? 'Sending...' : 'Send Invitation ❤️' }}
                </PrimaryButton>
            </form>
        </div>
    </div>
</template>
