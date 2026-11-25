<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, Send } from '@lucide/vue';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import SegmentedTabs from '@/components/SegmentedTabs.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface NoteItem {
    id: number;
    message: string;
    person_name: string | null;
    person_avatar: string | null;
    read_at: string | null;
    created_at: string;
}

interface Partner {
    id: number;
    name: string;
    avatar?: string;
}

const props = defineProps<{
    sent: NoteItem[];
    received: NoteItem[];
    partner: Partner | null;
    default_messages: string[];
}>();

type Tab = 'send' | 'received' | 'sent';

const activeTab = ref<Tab>('send');

// Notes count as read once the Received tab has actually been opened.
const unread = ref(props.received.filter((note) => !note.read_at).length);

const tabs = computed<{ key: Tab; label: string; count?: number }[]>(() => [
    { key: 'send', label: 'Send' },
    { key: 'received', label: 'Received', count: unread.value },
    { key: 'sent', label: 'Sent' },
]);

watch(activeTab, (tab) => {
    if (tab === 'received' && unread.value > 0) {
        unread.value = 0;
        void axios.post('/love-notes/mark-read').catch(() => undefined);
    }
});
const customMessage = ref('');

const form = useForm({ message: '' });

function selectMessage(msg: string): void {
    form.message = msg;
    customMessage.value = '';
}

function sendNote(): void {
    form.post('/love-notes', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            customMessage.value = '';
            activeTab.value = 'sent';
        },
    });
}

function formatTime(iso: string): string {
    const date = new Date(iso);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) {
        return 'Just now';
    }

    if (diffMins < 60) {
        return `${diffMins}m ago`;
    }

    const diffHours = Math.floor(diffMins / 60);

    if (diffHours < 24) {
        return `${diffHours}h ago`;
    }

    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="Love notes" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            title="Love notes"
            description="Send a little love to your partner"
        />

        <SegmentedTabs v-model="activeTab" :options="tabs" />

        <!-- Send tab -->
        <template v-if="activeTab === 'send'">
            <div v-if="!partner" class="card-premium">
                <EmptyState
                    emoji="💔"
                    title="No partner connected"
                    description="Connect with your partner to send love notes."
                    action-label="Connect partner"
                    @action="router.visit('/partner')"
                />
            </div>

            <template v-else>
                <section class="space-y-3">
                    <SectionHeader title="Quick messages" />
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="msg in default_messages"
                            :key="msg"
                            type="button"
                            class="chip max-w-full py-2 text-left whitespace-normal"
                            :aria-pressed="form.message === msg"
                            @click="selectMessage(msg)"
                        >
                            {{ msg }}
                        </button>
                    </div>
                </section>

                <section class="space-y-3">
                    <SectionHeader title="Or write your own" />
                    <div>
                        <textarea
                            v-model="form.message"
                            placeholder="Write something sweet... (max 200 characters)"
                            aria-label="Your message"
                            maxlength="200"
                            rows="4"
                            class="input-premium"
                        />
                        <p
                            class="mt-1 text-right text-xs text-muted-foreground tabular-nums"
                        >
                            {{ form.message.length }}/200
                        </p>
                    </div>

                    <InputError :message="form.errors.message" />

                    <PrimaryButton
                        full-width
                        :loading="form.processing"
                        :disabled="!form.message.trim()"
                        @click="sendNote"
                    >
                        <Send class="size-4" aria-hidden="true" />
                        Send to {{ partner.name }}
                    </PrimaryButton>
                </section>
            </template>
        </template>

        <!-- Received tab -->
        <div
            v-else-if="activeTab === 'received'"
            :class="[
                'card-premium',
                received.length > 0 && 'divide-y divide-border px-5',
            ]"
        >
            <template v-if="received.length > 0">
                <div
                    v-for="note in received"
                    :key="note.id"
                    class="flex items-start gap-3.5 py-4"
                >
                    <div
                        class="icon-tile h-11 w-11 font-display text-lg text-primary"
                        aria-hidden="true"
                    >
                        {{ note.person_name?.charAt(0) ?? '?' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] leading-relaxed text-foreground">
                            {{ note.message }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            From {{ note.person_name }} ·
                            {{ formatTime(note.created_at) }}
                        </p>
                    </div>
                </div>
            </template>
            <EmptyState
                v-else
                emoji="📥"
                title="No notes yet"
                description="Your partner hasn't sent you a love note yet."
            />
        </div>

        <!-- Sent tab -->
        <div
            v-else-if="activeTab === 'sent'"
            :class="[
                'card-premium',
                sent.length > 0 && 'divide-y divide-border px-5',
            ]"
        >
            <template v-if="sent.length > 0">
                <div v-for="note in sent" :key="note.id" class="py-4">
                    <p class="text-[15px] leading-relaxed text-foreground">
                        {{ note.message }}
                    </p>
                    <p
                        class="mt-1 flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground"
                    >
                        <span
                            >To {{ note.person_name }} ·
                            {{ formatTime(note.created_at) }}</span
                        >
                        <span
                            v-if="note.read_at"
                            class="inline-flex items-center gap-0.5 font-medium text-success"
                        >
                            <Check class="size-3.5" aria-hidden="true" />
                            Seen
                        </span>
                    </p>
                </div>
            </template>
            <EmptyState
                v-else
                emoji="📤"
                title="Nothing sent yet"
                description="Send your first love note above."
            />
        </div>
    </div>
</template>
