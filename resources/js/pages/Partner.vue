<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Clock } from '@lucide/vue';
import { ref } from 'vue';
import PartnerController from '@/actions/App/Http/Controllers/PartnerController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

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
}

interface Props {
    partner: Partner | null;
    pendingInvitation: PendingInvitation | null;
    connectedSince: string | null;
}

defineProps<Props>();

const showDisconnectConfirm = ref(false);
const showRevokeConfirm = ref(false);
const inviteForm = useForm({ email: '' });
const disconnectForm = useForm({});
const resendForm = useForm({});
const revokeForm = useForm({});

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

function revokeInvite(): void {
    revokeForm.delete(PartnerController.revokeInvite().url);
}

function disconnect(): void {
    disconnectForm.delete(PartnerController.disconnect().url);
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

const labelClass = 'mb-1.5 block text-[13px] font-medium text-foreground/80';
</script>

<template>
    <Head title="Partner" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Your connection"
            title="Partner"
            description="Manage your connection"
        />

        <!-- Connected partner -->
        <template v-if="partner">
            <section class="card-feature p-6">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-3xl ring-1 ring-white/20"
                        aria-hidden="true"
                    >
                        {{ partner.avatar || '💕' }}
                    </div>
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <p
                            class="flex items-center gap-2 eyebrow text-gold-soft"
                        >
                            <span
                                class="size-1.5 rounded-full bg-success"
                                aria-hidden="true"
                            />
                            Connected
                        </p>
                        <h2
                            class="truncate text-[1.75rem] leading-tight font-medium"
                        >
                            {{ partner.display_name ?? partner.name }}
                        </h2>
                    </div>
                </div>
                <template v-if="connectedSince">
                    <div class="my-5 hairline-gold opacity-60" />
                    <p class="text-sm text-white/80">
                        Since {{ formatDate(connectedSince) }}
                    </p>
                </template>
            </section>

            <!-- Disconnect -->
            <section class="space-y-4 card-premium p-6">
                <div class="space-y-1.5">
                    <h3 class="text-lg font-semibold text-foreground">
                        Disconnect partner
                    </h3>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        This ends the connection for both of you, and
                        {{ partner.display_name ?? partner.name }} is told.
                        Nothing is deleted:
                    </p>
                    <ul
                        class="list-disc space-y-1 pl-5 text-sm leading-relaxed text-muted-foreground"
                    >
                        <li>
                            Date nights you planned together stay in both your
                            histories.
                        </li>
                        <li>
                            Love notes stay with both of you, but you can no
                            longer send new ones.
                        </li>
                        <li>
                            Moments and calendar events stay with whoever added
                            them. You will no longer see each other's.
                        </li>
                    </ul>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        You can reconnect by sending a new invitation, and you
                        will see each other's moments and events again.
                    </p>
                </div>

                <SecondaryButton
                    variant="outline"
                    size="sm"
                    full-width
                    class="border-destructive/30! text-destructive! hover:border-destructive! hover:bg-destructive/10!"
                    @click="showDisconnectConfirm = true"
                >
                    Disconnect partner
                </SecondaryButton>

                <ConfirmDialog
                    v-model:open="showDisconnectConfirm"
                    title="Disconnect from your partner?"
                    description="You both keep the date nights you planned together and your love notes. You each keep your own moments and calendar events, and stop seeing each other's. Your partner will be told."
                    confirm-label="Disconnect"
                    @confirm="disconnect"
                />
            </section>
        </template>

        <!-- Pending invitation -->
        <template v-else-if="pendingInvitation">
            <section class="space-y-4 card-premium p-6">
                <div class="flex items-center gap-3.5">
                    <span class="icon-tile h-12 w-12" aria-hidden="true">
                        <Clock class="size-5 text-gold" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-foreground">
                            Invitation pending
                        </h2>
                        <p class="truncate text-sm text-muted-foreground">
                            Sent to {{ pendingInvitation.email }}
                        </p>
                    </div>
                </div>
                <p class="text-xs text-muted-foreground">
                    Expires {{ formatDate(pendingInvitation.expires_at) }}
                </p>
                <PrimaryButton
                    full-width
                    :disabled="resendForm.processing"
                    @click="resendInvite"
                >
                    {{
                        resendForm.processing
                            ? 'Sending...'
                            : 'Resend invitation'
                    }}
                </PrimaryButton>
                <SecondaryButton
                    variant="outline"
                    size="sm"
                    full-width
                    class="border-destructive/30! text-destructive! hover:border-destructive! hover:bg-destructive/10!"
                    :disabled="revokeForm.processing"
                    @click="showRevokeConfirm = true"
                >
                    Revoke invitation
                </SecondaryButton>

                <ConfirmDialog
                    v-model:open="showRevokeConfirm"
                    title="Revoke this invitation?"
                    :description="`The link sent to ${pendingInvitation.email} will stop working. You can send a new invitation at any time.`"
                    confirm-label="Revoke"
                    @confirm="revokeInvite"
                />
            </section>

            <!-- Send new invite -->
            <section class="space-y-4 card-premium p-6">
                <h3 class="text-lg font-semibold text-foreground">
                    Invite someone else
                </h3>
                <form class="space-y-4" @submit.prevent="sendInvite">
                    <div>
                        <label :class="labelClass" for="partner-email"
                            >Partner's email</label
                        >
                        <input
                            id="partner-email"
                            v-model="inviteForm.email"
                            type="email"
                            placeholder="partner@email.com"
                            class="input-premium"
                            required
                        />
                        <p
                            v-if="inviteForm.errors.email"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ inviteForm.errors.email }}
                        </p>
                    </div>
                    <SecondaryButton
                        type="submit"
                        variant="outline"
                        full-width
                        :disabled="inviteForm.processing"
                    >
                        {{
                            inviteForm.processing
                                ? 'Sending...'
                                : 'Send new invitation'
                        }}
                    </SecondaryButton>
                </form>
            </section>
        </template>

        <!-- No partner -->
        <section v-else class="card-premium p-6">
            <EmptyState
                emoji="💌"
                title="Invite your partner"
                description="Invite your partner to begin your journey together."
            />
            <form class="space-y-4" @submit.prevent="sendInvite">
                <div>
                    <label :class="labelClass" for="partner-email"
                        >Partner's email</label
                    >
                    <input
                        id="partner-email"
                        v-model="inviteForm.email"
                        type="email"
                        placeholder="partner@email.com"
                        class="input-premium"
                        required
                    />
                    <p
                        v-if="inviteForm.errors.email"
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ inviteForm.errors.email }}
                    </p>
                </div>
                <PrimaryButton
                    type="submit"
                    full-width
                    :disabled="inviteForm.processing"
                >
                    {{
                        inviteForm.processing ? 'Sending...' : 'Send invitation'
                    }}
                </PrimaryButton>
            </form>
        </section>
    </div>
</template>
