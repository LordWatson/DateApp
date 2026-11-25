<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Clock, X } from '@lucide/vue';
import { ref } from 'vue';
import PartnerController from '@/actions/App/Http/Controllers/PartnerController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import ConnectionChoices from '@/components/ConnectionChoices.vue';
import type { SharedHistory } from '@/components/ConnectionChoices.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import InviteLink from '@/components/InviteLink.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { after } from '@/routes/partner';
import { edit as editProfile } from '@/routes/profile';

defineOptions({ layout: MobileLayout });

interface Partner {
    id: number;
    name: string;
    display_name?: string;
    avatar?: string;
    created_at: string;
}

interface PendingInvitation {
    /** Null for an invitation shared as a link. */
    email: string | null;
    expires_at: string;
}

interface Props {
    partner: Partner | null;
    pendingInvitation: PendingInvitation | null;
    connectedSince: string | null;
    /** The link for the user's own pending invitation. */
    inviteUrl: string | null;
    /** Set while the connection is paused. */
    paused: { at: string | null; by_me: boolean } | null;
    /** What ending the connection would ask about (connected users only). */
    shared: SharedHistory | null;
    /** Who the user was last connected to, when they have no partner now. */
    formerPartner: { name: string } | null;
}

defineProps<Props>();

const showDisconnectConfirm = ref(false);
const showPauseConfirm = ref(false);
const showRevokeConfirm = ref(false);
const inviteForm = useForm({ email: '' });
const disconnectForm = useForm({
    keep_plans: true,
    keep_notes: true,
    password: '',
});
const pauseForm = useForm({});
const resendForm = useForm({});
const revokeForm = useForm({});
const linkForm = useForm({});

function sendInvite(): void {
    inviteForm.post(PartnerController.sendInvite().url, {
        onSuccess: () => {
            inviteForm.reset();
        },
    });
}

function createLink(): void {
    linkForm.post(PartnerController.createLink().url, { preserveScroll: true });
}

function resendInvite(): void {
    resendForm.post(PartnerController.resendInvite().url);
}

function revokeInvite(): void {
    revokeForm.delete(PartnerController.revokeInvite().url);
}

function disconnect(): void {
    disconnectForm.delete(PartnerController.disconnect().url, {
        preserveScroll: true,
        onSuccess: () => {
            showDisconnectConfirm.value = false;
        },
        onFinish: () => disconnectForm.reset('password'),
    });
}

function pause(): void {
    pauseForm.post(PartnerController.pause().url, { preserveScroll: true });
}

function resume(): void {
    pauseForm.post(PartnerController.resume().url, { preserveScroll: true });
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
                            {{ paused ? 'Paused' : 'Connected' }}
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

            <!-- Take a break -->
            <section class="space-y-4 card-premium p-6">
                <template v-if="paused">
                    <div class="space-y-1.5">
                        <h3 class="text-lg font-semibold text-foreground">
                            Your connection is paused
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            {{
                                paused.by_me
                                    ? 'You paused it'
                                    : `${partner.display_name ?? partner.name} paused it`
                            }}<template v-if="paused.at">
                                on {{ formatDate(paused.at) }}</template
                            >. You both keep everything you have shared.
                            Notifications, love notes and planning together are
                            on hold.
                        </p>
                    </div>
                    <PrimaryButton
                        v-if="paused.by_me"
                        full-width
                        :disabled="pauseForm.processing"
                        @click="resume"
                    >
                        Resume connection
                    </PrimaryButton>
                    <p v-else class="text-sm text-muted-foreground">
                        Only {{ partner.display_name ?? partner.name }} can
                        resume it.
                    </p>
                </template>
                <template v-else>
                    <div class="space-y-1.5">
                        <h3 class="text-lg font-semibold text-foreground">
                            Take a break
                        </h3>
                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            Pause without losing anything. You stay connected
                            and keep your shared history, but you stop getting
                            notifications about each other, and love notes and
                            planning together are on hold until you resume.
                        </p>
                    </div>
                    <SecondaryButton
                        variant="outline"
                        size="sm"
                        full-width
                        @click="showPauseConfirm = true"
                    >
                        Pause connection
                    </SecondaryButton>
                    <ConfirmDialog
                        v-model:open="showPauseConfirm"
                        title="Pause your connection?"
                        :description="`${partner.display_name ?? partner.name} is told once. Nothing is deleted, and only you can resume it.`"
                        confirm-label="Pause"
                        @confirm="pause"
                    />
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
                        Nothing is deleted, and you choose what stays in your
                        own account:
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
            </section>

            <!-- End connection: choices, then the password -->
            <div
                v-if="showDisconnectConfirm && shared"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="end-connection-title"
                @click.self="showDisconnectConfirm = false"
            >
                <div
                    class="max-h-[90vh] w-full max-w-md overflow-y-auto card-premium rounded-b-none p-6 sm:rounded-3xl"
                >
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <h2
                            id="end-connection-title"
                            class="text-xl font-semibold text-foreground"
                        >
                            Disconnect from
                            {{ partner.display_name ?? partner.name }}?
                        </h2>
                        <button
                            type="button"
                            class="-mt-1 -mr-2 flex size-10 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                            aria-label="Close"
                            @click="showDisconnectConfirm = false"
                        >
                            <X class="size-5" aria-hidden="true" />
                        </button>
                    </div>

                    <form class="space-y-5" @submit.prevent="disconnect">
                        <ConnectionChoices
                            v-model:keep-plans="disconnectForm.keep_plans"
                            v-model:keep-notes="disconnectForm.keep_notes"
                            :shared="shared"
                            :name="partner.display_name ?? partner.name"
                        />

                        <p
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            Want a copy first?
                            <Link
                                :href="editProfile()"
                                class="font-medium text-primary underline underline-offset-4"
                                >Download my data</Link
                            >
                            in Settings.
                        </p>

                        <div>
                            <label :class="labelClass" for="disconnect-password"
                                >Your password, to confirm</label
                            >
                            <input
                                id="disconnect-password"
                                v-model="disconnectForm.password"
                                type="password"
                                autocomplete="current-password"
                                class="input-premium"
                                required
                            />
                            <InputError
                                :message="disconnectForm.errors.password"
                                class="mt-1"
                            />
                        </div>

                        <div class="flex flex-col gap-3">
                            <SecondaryButton
                                type="submit"
                                variant="outline"
                                full-width
                                class="border-destructive/30! text-destructive! hover:border-destructive! hover:bg-destructive/10!"
                                :disabled="disconnectForm.processing"
                            >
                                Disconnect
                            </SecondaryButton>
                            <SecondaryButton
                                variant="ghost"
                                full-width
                                @click="showDisconnectConfirm = false"
                            >
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                </div>
            </div>
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
                            {{
                                pendingInvitation.email
                                    ? `Sent to ${pendingInvitation.email}`
                                    : 'Shared as a link'
                            }}
                        </p>
                    </div>
                </div>
                <p class="text-xs text-muted-foreground">
                    Expires {{ formatDate(pendingInvitation.expires_at) }}
                </p>
                <InviteLink v-if="inviteUrl" :url="inviteUrl" />
                <PrimaryButton
                    v-if="pendingInvitation.email"
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
                    description="The invitation link will stop working. You can send a new invitation at any time."
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
                <div class="mt-4 space-y-2 border-t border-border pt-4">
                    <p class="text-sm text-muted-foreground">
                        No email address to hand? Create a link and send it
                        yourself.
                    </p>
                    <SecondaryButton
                        variant="ghost"
                        full-width
                        :disabled="linkForm.processing"
                        @click="createLink"
                    >
                        Replace it with a link
                    </SecondaryButton>
                </div>
            </section>
        </template>

        <!-- No partner -->
        <section
            v-if="!partner && formerPartner"
            class="space-y-3 card-premium p-6"
        >
            <h2 class="text-lg font-semibold text-foreground">
                After {{ formerPartner.name }}
            </h2>
            <p class="text-sm leading-relaxed text-muted-foreground">
                The date nights you planned together and your love notes are
                still in your account unless you chose otherwise.
            </p>
            <Link
                :href="after().url"
                class="inline-flex min-h-10 items-center text-sm font-medium text-primary underline underline-offset-4"
                >Choose what you keep</Link
            >
        </section>

        <section v-if="!partner && !pendingInvitation" class="card-premium p-6">
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
            <div class="mt-4 space-y-2 border-t border-border pt-4">
                <p class="text-sm text-muted-foreground">
                    No email address to hand? Create a link and send it
                    yourself.
                </p>
                <SecondaryButton
                    variant="ghost"
                    full-width
                    :disabled="linkForm.processing"
                    @click="createLink"
                >
                    Share a link instead
                </SecondaryButton>
            </div>
        </section>
    </div>
</template>
