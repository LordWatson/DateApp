<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckCheck } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
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
        partner_liked_plan: '❤️',
        daily_challenge: '✨',
        partner_added_date_night_to_calendar: '📅',
        achievement_unlocked: '🏆',
        questionnaire_reminder: '⏰',
        partner_connected: '💞',
        partner_left: '👋',
        invitation_declined: '✉️',
        plan_failed: '⚠️',
        calendar_reminder: '🗓️',
        invitation_expired: '⌛',
        data_export_ready: '📦',
        partner_nudge: '⏳',
        love_note_reaction: '💗',
        connection_anniversary: '💞',
    };

    return map[type] ?? '🔔';
}

function destination(notification: NotificationItem): string | null {
    const data = notification.data ?? {};

    switch (notification.type) {
        case 'partner_completed':
        case 'partner_nudge':
        case 'plan_failed':
            return data.questionnaire_slug
                ? `/questionnaires/${data.questionnaire_slug}`
                : '/questionnaires';
        case 'questionnaire_reminder':
            return '/questionnaires';
        case 'date_night_plan_ready':
        case 'partner_liked_plan':
        case 'partner_viewed_plan':
            return data.plan_id
                ? `/date-night/${data.plan_id}`
                : '/date-night/history';
        case 'partner_added_date_night_to_calendar':
        case 'calendar_reminder':
            return data.calendar_event_id
                ? `/calendar/${data.calendar_event_id}`
                : '/calendar';
        case 'love_note_received':
        case 'love_note_reaction':
            return data.love_note_id
                ? `/love-notes/${data.love_note_id}`
                : '/love-notes';
        case 'moment_created':
            return data.moment_id ? `/moments/${data.moment_id}` : '/moments';
        case 'achievement_unlocked':
            return '/achievements';
        case 'daily_challenge':
            return '/dashboard';
        case 'partner_connected':
        case 'connection_anniversary':
        case 'partner_left':
        case 'invitation_declined':
        case 'invitation_expired':
            return '/partner';
        case 'data_export_ready':
            return '/settings/profile';
        default:
            return null;
    }
}

function handleNotificationClick(notification: NotificationItem): void {
    const redirect = (): void => {
        const url = destination(notification);

        if (url) {
            router.visit(url);
        }
    };

    router.post(
        `/notifications/${notification.id}/read`,
        {},
        {
            preserveScroll: true,
            onSuccess: redirect,
        },
    );
}

function markAllRead(): void {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader title="Notifications" description="Stay up to date">
            <button
                v-if="notifications.some((n) => !n.read_at)"
                type="button"
                class="-mr-2 inline-flex min-h-10 items-center gap-1.5 rounded-full px-3 text-[13px] font-medium text-primary transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                @click="markAllRead"
            >
                <CheckCheck class="size-4" aria-hidden="true" />
                Mark all read
            </button>
        </PageHeader>

        <div v-if="notifications.length > 0" class="card-premium p-2">
            <button
                v-for="notification in notifications"
                :key="notification.id"
                type="button"
                class="list-row w-full items-start text-left focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                @click="handleNotificationClick(notification)"
            >
                <span class="icon-tile h-11 w-11 text-xl" aria-hidden="true">{{
                    typeEmoji(notification.type)
                }}</span>
                <span class="block min-w-0 flex-1">
                    <span
                        :class="[
                            'block text-sm text-foreground',
                            notification.read_at
                                ? 'font-medium'
                                : 'font-semibold',
                        ]"
                        >{{ notification.title }}</span
                    >
                    <span
                        v-if="notification.body"
                        class="mt-0.5 block text-sm text-muted-foreground"
                        >{{ notification.body }}</span
                    >
                    <span class="mt-1 block text-xs text-muted-foreground">{{
                        formatTime(notification.created_at)
                    }}</span>
                </span>
                <span
                    v-if="!notification.read_at"
                    class="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                >
                    <span class="sr-only">Unread</span>
                </span>
            </button>
        </div>

        <div v-else class="card-premium">
            <EmptyState
                emoji="🔔"
                title="All caught up!"
                description="You have no notifications right now."
            />
        </div>
    </div>
</template>
