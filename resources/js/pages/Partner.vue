<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, Clock, TriangleAlert } from '@lucide/vue';
import { ref } from 'vue';
import PartnerController from '@/actions/App/Http/Controllers/PartnerController';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
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
const page = usePage<{
    auth: Auth;
    flash?: { success?: string; error?: string };
}>();

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

        <!-- Flash messages -->
        <div v-if="flash?.success || flash?.error" class="space-y-3">
            <div
                v-if="flash?.success"
                class="flex items-start gap-2.5 rounded-2xl border border-success/25 bg-success/10 px-4 py-3 text-sm text-success"
                role="status"
            >
                <CircleCheck
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                {{ flash.success }}
            </div>
            <div
                v-if="flash?.error"
                class="flex items-start gap-2.5 rounded-2xl border border-destructive/25 bg-destructive/10 px-4 py-3 text-sm text-destructive"
                role="alert"
            >
                <CircleAlert
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                {{ flash.error }}
            </div>
        </div>

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
                        This will remove your connection. You can reconnect at
                        any time by sending a new invitation.
                    </p>
                </div>

                <SecondaryButton
                    v-if="!showDisconnectConfirm"
                    variant="outline"
                    size="sm"
                    full-width
                    class="border-destructive/30! text-destructive! hover:border-destructive! hover:bg-destructive/10!"
                    @click="showDisconnectConfirm = true"
                >
                    Disconnect partner
                </SecondaryButton>

                <div v-else class="space-y-3">
                    <p
                        class="flex items-start gap-2.5 rounded-2xl border border-destructive/25 bg-destructive/10 px-4 py-3 text-sm text-destructive"
                        role="alert"
                    >
                        <TriangleAlert
                            class="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        Are you sure? This will disconnect both of you.
                    </p>
                    <div class="flex gap-3">
                        <SecondaryButton
                            variant="outline"
                            size="sm"
                            class="flex-1"
                            @click="showDisconnectConfirm = false"
                        >
                            Cancel
                        </SecondaryButton>
                        <button
                            class="min-h-10 flex-1 rounded-full bg-destructive px-5 text-sm font-semibold text-white transition-all hover:brightness-110 focus-visible:ring-2 focus-visible:ring-destructive focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="disconnectForm.processing"
                            @click="disconnect"
                        >
                            {{
                                disconnectForm.processing
                                    ? 'Disconnecting...'
                                    : 'Yes, disconnect'
                            }}
                        </button>
                    </div>
                </div>
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
