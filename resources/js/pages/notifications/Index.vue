<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface NotificationItem {
    id: number;
    type: string;
    title: string;
    body: string | null;
    data: Record<string, unknown> | null;
    read_at: string | null;
    created_at: string;
}

defineProps<{ notifications: NotificationItem[] }>();

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

function typeEmoji(type: string): string {
    const map: Record<string, string> = {
        partner_completed: '🎉',
        compatibility_ready: '💕',
        date_night_plan_ready: '❤️',
        love_note_received: '💌',
        moment_created: '📸',
        partner_viewed_plan: '👀',
        daily_challenge: '✨',
    };

    return map[type] ?? '🔔';
}

function handleNotificationClick(notification: NotificationItem): void {
    const redirect = (): void => {
        if (notification.type === 'partner_completed' && notification.data?.questionnaire_slug) {
            router.visit(`/questionnaires/${notification.data.questionnaire_slug}`);
        } else if (notification.type === 'date_night_plan_ready' && notification.data?.plan_id) {
            router.visit(`/date-night/${notification.data.plan_id}`);
        } else if (notification.type === 'love_note_received' && notification.data?.love_note_id) {
            router.visit(`/love-notes/${notification.data.love_note_id}`);
        } else if (notification.type === 'love_note_received') {
            router.visit('/love-notes');
        } else if (notification.type === 'moment_created' && notification.data?.moment_id) {
            router.visit(`/moments/${notification.data.moment_id}`);
        } else if (notification.type === 'moment_created') {
            router.visit('/moments');
        }
    };

    router.post(`/notifications/${notification.id}/read`, {}, {
        preserveScroll: true,
        onSuccess: redirect,
    });
}

function markAllRead(): void {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications 🔔" />

    <div class="min-h-screen px-4 py-6 pb-28">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">🔔 Notifications</h1>
                <p class="mt-1 text-sm text-muted-foreground">Stay up to date</p>
            </div>
            <button
                v-if="notifications.some(n => !n.read_at)"
                class="text-sm font-semibold text-pink-500"
                @click="markAllRead"
            >
                Mark all read
            </button>
        </div>

        <div v-if="notifications.length > 0" class="space-y-3">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="[
                    'cursor-pointer rounded-3xl p-5 shadow-xl transition-transform duration-200 hover:scale-[1.01]',
                    notification.read_at ? 'bg-card' : 'bg-primary/5 border border-primary/20',
                ]"
                @click="handleNotificationClick(notification)"
            >
                <div class="flex items-start gap-3">
                    <span class="text-2xl">{{ typeEmoji(notification.type) }}</span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-foreground">{{ notification.title }}</p>
                        <p v-if="notification.body" class="mt-1 text-xs text-muted-foreground">{{ notification.body }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ formatTime(notification.created_at) }}</p>
                    </div>
                    <div v-if="!notification.read_at" class="mt-1 h-2 w-2 rounded-full bg-pink-500" />
                </div>
            </div>
        </div>

        <EmptyState
            v-else
            emoji="🔔"
            title="All caught up!"
            description="You have no notifications right now."
        />
    </div>
</template>
