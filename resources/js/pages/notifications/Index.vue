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
    };

    return map[type] ?? '🔔';
}

function handleNotificationClick(notification: NotificationItem): void {
    const redirect = (): void => {
        if (
            notification.type === 'partner_completed' &&
            notification.data?.questionnaire_slug
        ) {
            router.visit(
                `/questionnaires/${notification.data.questionnaire_slug}`,
            );
        } else if (
            notification.type === 'date_night_plan_ready' &&
            notification.data?.plan_id
        ) {
            router.visit(`/date-night/${notification.data.plan_id}`);
        } else if (
            notification.type === 'partner_liked_plan' &&
            notification.data?.plan_id
        ) {
            router.visit(`/date-night/${notification.data.plan_id}`);
        } else if (
            notification.type === 'love_note_received' &&
            notification.data?.love_note_id
        ) {
            router.visit(`/love-notes/${notification.data.love_note_id}`);
        } else if (notification.type === 'love_note_received') {
            router.visit('/love-notes');
        } else if (
            notification.type === 'moment_created' &&
            notification.data?.moment_id
        ) {
            router.visit(`/moments/${notification.data.moment_id}`);
        } else if (notification.type === 'moment_created') {
            router.visit('/moments');
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
