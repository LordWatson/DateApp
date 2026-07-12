<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
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

defineProps<{
    sent: NoteItem[];
    received: NoteItem[];
    partner: Partner | null;
    default_messages: string[];
}>();

const activeTab = ref<'send' | 'received' | 'sent'>('send');
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
    <Head title="Love Notes 💌" />

    <div class="min-h-screen px-4 py-6 pb-28">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">💌 Love Notes</h1>
            <p class="mt-1 text-sm text-gray-500">Send a little love to your partner</p>
        </div>

        <!-- Tabs -->
        <div class="mb-6 flex rounded-2xl bg-gray-100 p-1">
            <button
                v-for="tab in [{ key: 'send', label: '✉️ Send' }, { key: 'received', label: '📥 Received' }, { key: 'sent', label: '📤 Sent' }]"
                :key="tab.key"
                :class="[
                    'flex-1 rounded-xl py-2 text-xs font-semibold transition-all',
                    activeTab === tab.key ? 'bg-white text-pink-600 shadow-sm' : 'text-gray-500',
                ]"
                @click="activeTab = tab.key as 'send' | 'received' | 'sent'"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Send Tab -->
        <div v-if="activeTab === 'send'">
            <div v-if="!partner" class="rounded-3xl bg-white p-6 text-center shadow-xl">
                <p class="text-4xl">💔</p>
                <p class="mt-2 font-semibold text-gray-800">No partner connected</p>
                <p class="mt-1 text-sm text-gray-500">Connect with your partner to send love notes.</p>
                <div class="mt-4">
                    <PrimaryButton full-width @click="router.visit('/partner')">
                        Connect Partner
                    </PrimaryButton>
                </div>
            </div>

            <div v-else class="space-y-4">
                <p class="text-sm font-semibold text-gray-600">Quick messages</p>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="msg in default_messages"
                        :key="msg"
                        :class="[
                            'rounded-2xl border p-3 text-left text-sm transition-all',
                            form.message === msg
                                ? 'border-pink-400 bg-pink-50 text-pink-700'
                                : 'border-gray-200 bg-white text-gray-700',
                        ]"
                        @click="selectMessage(msg)"
                    >
                        {{ msg }}
                    </button>
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-gray-600">Or write your own</p>
                    <textarea
                        v-model="form.message"
                        placeholder="Write something sweet... (max 200 characters)"
                        maxlength="200"
                        rows="3"
                        class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-pink-400 focus:outline-none focus:ring-2 focus:ring-pink-200"
                    />
                    <p class="mt-1 text-right text-xs text-gray-400">{{ form.message.length }}/200</p>
                </div>

                <p v-if="form.errors.message" class="text-sm text-red-500">{{ form.errors.message }}</p>

                <PrimaryButton
                    full-width
                    :loading="form.processing"
                    :disabled="!form.message.trim()"
                    @click="sendNote"
                >
                    Send to {{ partner.name }} 💌
                </PrimaryButton>
            </div>
        </div>

        <!-- Received Tab -->
        <div v-else-if="activeTab === 'received'">
            <div v-if="received.length > 0" class="space-y-3">
                <div
                    v-for="note in received"
                    :key="note.id"
                    class="rounded-3xl bg-white p-5 shadow-xl"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg"
                             style="background: linear-gradient(135deg, #EC4899, #9333EA); color: white;">
                            {{ note.person_name?.charAt(0) ?? '?' }}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">{{ note.message }}</p>
                            <p class="mt-1 text-xs text-gray-400">
                                From {{ note.person_name }} · {{ formatTime(note.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <EmptyState v-else emoji="📥" title="No notes yet" description="Your partner hasn't sent you a love note yet." />
        </div>

        <!-- Sent Tab -->
        <div v-else-if="activeTab === 'sent'">
            <div v-if="sent.length > 0" class="space-y-3">
                <div
                    v-for="note in sent"
                    :key="note.id"
                    class="rounded-3xl bg-white p-5 shadow-xl"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">{{ note.message }}</p>
                            <p class="mt-1 text-xs text-gray-400">
                                To {{ note.person_name }} · {{ formatTime(note.created_at) }}
                                <span v-if="note.read_at" class="ml-1 text-emerald-500">· Seen ✓</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <EmptyState v-else emoji="📤" title="Nothing sent yet" description="Send your first love note above." />
        </div>
    </div>
</template>
