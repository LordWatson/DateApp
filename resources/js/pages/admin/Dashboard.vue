<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CircleAlert,
    CircleCheck,
    ClipboardList,
    Flag,
    Heart,
    Hourglass,
    Mail,
    Moon,
    ScrollText,
    Target,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    stats: {
        total_users: number;
        couples: number;
        active_questionnaires: number;
        completions_today: number;
        plans_today: number;
        love_notes_today: number;
        failed_jobs: number;
        queued_jobs: number;
    };
    newest_users: Array<{
        id: number;
        name: string;
        email: string;
        created_at: string;
    }>;
    daily_users: Array<{ date: string; count: number }>;
    daily_completions: Array<{ date: string; count: number }>;
}>();

const statCards = computed(() => [
    {
        label: 'Total users',
        value: props.stats.total_users,
        icon: Users,
        color: 'text-primary',
        href: '/admin/users',
    },
    {
        label: 'Couples',
        value: Math.floor(props.stats.couples),
        icon: Heart,
        color: 'text-primary',
        href: '/admin/couples',
    },
    {
        label: 'Active questionnaires',
        value: props.stats.active_questionnaires,
        icon: ClipboardList,
        color: 'text-primary',
        href: '/admin/questionnaires',
    },
    {
        label: 'Completions today',
        value: props.stats.completions_today,
        icon: CircleCheck,
        color: 'text-success',
        href: '/admin/analytics',
    },
    {
        label: 'Plans today',
        value: props.stats.plans_today,
        icon: Moon,
        color: 'text-primary',
        href: '/admin/analytics',
    },
    {
        label: 'Love notes today',
        value: props.stats.love_notes_today,
        icon: Mail,
        color: 'text-primary',
        href: '/admin/analytics',
    },
    {
        label: 'Queued jobs',
        value: props.stats.queued_jobs,
        icon: Hourglass,
        color: 'text-warning',
        href: '/admin',
    },
    {
        label: 'Failed jobs',
        value: props.stats.failed_jobs,
        icon: CircleAlert,
        color:
            props.stats.failed_jobs > 0
                ? 'text-destructive'
                : 'text-muted-foreground',
        href: '/admin',
    },
]);

const quickActions = [
    {
        label: 'New questionnaire',
        href: '/admin/questionnaires/create',
        icon: ClipboardList,
    },
    { label: 'Manage challenges', href: '/admin/challenges', icon: Target },
    { label: 'Feature flags', href: '/admin/feature-flags', icon: Flag },
    { label: 'Audit logs', href: '/admin/audit-logs', icon: ScrollText },
];
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">
                Admin dashboard
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Welcome back. Here's what's happening today.
            </p>
        </div>

        <!-- Stat cards -->
        <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
            <Link
                v-for="card in statCards"
                :key="card.label"
                :href="card.href"
                class="card-hover card-premium rounded-2xl p-5"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="eyebrow">{{ card.label }}</p>
                    <component
                        :is="card.icon"
                        :class="['size-4 shrink-0', card.color]"
                        aria-hidden="true"
                    />
                </div>
                <p
                    class="mt-3 font-display text-3xl font-semibold text-foreground tabular-nums"
                >
                    {{ card.value.toLocaleString() }}
                </p>
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Newest users -->
            <div class="card-premium rounded-2xl p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">
                        Newest registrations
                    </h2>
                    <Link
                        href="/admin/users"
                        class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        View all
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </div>
                <div class="divide-y divide-border">
                    <div
                        v-for="user in newest_users"
                        :key="user.id"
                        class="flex items-center gap-3 py-2.5"
                    >
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-primary-foreground gradient-primary"
                        >
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-medium text-foreground"
                            >
                                {{ user.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </div>
                        <Link
                            :href="`/admin/users/${user.id}`"
                            class="shrink-0 text-xs font-medium text-foreground hover:underline"
                            >View</Link
                        >
                    </div>
                    <p
                        v-if="!newest_users.length"
                        class="py-4 text-center text-sm text-muted-foreground"
                    >
                        No users yet.
                    </p>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="card-premium rounded-2xl p-5">
                <h2 class="mb-4 text-lg font-semibold text-foreground">
                    Quick actions
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <Link
                        v-for="action in quickActions"
                        :key="action.href"
                        :href="action.href"
                        class="flex items-center gap-3 rounded-xl border border-border p-3 transition-colors hover:border-primary/40 hover:bg-accent"
                    >
                        <span class="icon-tile size-9 text-primary">
                            <component
                                :is="action.icon"
                                class="size-4"
                                aria-hidden="true"
                            />
                        </span>
                        <span class="text-sm font-medium text-foreground">{{
                            action.label
                        }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
