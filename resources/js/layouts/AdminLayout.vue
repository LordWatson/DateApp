<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BarChart3,
    CircleAlert,
    CircleCheck,
    ClipboardList,
    Flag,
    Heart,
    Images,
    LayoutDashboard,
    Menu,
    ScrollText,
    Settings,
    SlidersHorizontal,
    Sparkles,
    Target,
    Trophy,
    Users,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import type { AdminAbilities } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(
    () => page.props.flash as { success?: string; error?: string } | undefined,
);

const sidebarOpen = ref(true);
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const allNavItems: Array<{
    label: string;
    href: string;
    icon: unknown;
    exact?: boolean;
    can?: keyof AdminAbilities;
}> = [
    { label: 'Dashboard', href: '/admin', icon: LayoutDashboard, exact: true },
    { label: 'Users', href: '/admin/users', icon: Users },
    {
        label: 'Couples',
        href: '/admin/couples',
        icon: Heart,
        can: 'manageUsers',
    },
    {
        label: 'Questionnaires',
        href: '/admin/questionnaires',
        icon: ClipboardList,
        can: 'manageContent',
    },
    {
        label: 'Challenges',
        href: '/admin/challenges',
        icon: Target,
        can: 'manageContent',
    },
    {
        label: 'Achievements',
        href: '/admin/achievements',
        icon: Trophy,
        can: 'manageContent',
    },
    {
        label: 'Feature flags',
        href: '/admin/feature-flags',
        icon: Flag,
        can: 'superAdmin',
    },
    {
        label: 'Settings',
        href: '/admin/settings',
        icon: Settings,
        can: 'superAdmin',
    },
    // Email templates is left out: no mail class reads the table yet.
    {
        label: 'AI settings',
        href: '/admin/ai-settings',
        icon: SlidersHorizontal,
        can: 'superAdmin',
    },
    {
        label: 'AI prompts',
        href: '/admin/ai-prompts',
        icon: Sparkles,
        can: 'manageContent',
    },
    {
        label: 'Media library',
        href: '/admin/media',
        icon: Images,
        can: 'manageContent',
    },
    {
        label: 'Analytics',
        href: '/admin/analytics',
        icon: BarChart3,
        can: 'viewAnalytics',
    },
    {
        label: 'Audit logs',
        href: '/admin/audit-logs',
        icon: ScrollText,
        can: 'manageUsers',
    },
];

// Hide what this role would only get a 403 from. The routes enforce it too.
const navItems = computed(() =>
    allNavItems.filter(
        (item) => !item.can || page.props.auth?.can?.[item.can] === true,
    ),
);

const isActive = (href: string, exact = false) =>
    exact
        ? page.url === href || page.url.startsWith(`${href}?`)
        : page.url.startsWith(href);
</script>

<template>
    <div class="flex min-h-screen text-foreground">
        <!-- Sidebar -->
        <aside
            :class="[
                'sticky top-0 flex h-screen shrink-0 flex-col border-r border-border bg-card transition-all duration-300 ease-in-out',
                sidebarOpen ? 'w-64' : 'w-16',
            ]"
        >
            <!-- Wordmark -->
            <div
                class="flex h-16 items-center gap-2 border-b border-border px-4"
            >
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-full font-display text-sm font-semibold text-primary-foreground gradient-primary"
                    aria-hidden="true"
                >
                    D
                </span>
                <span
                    v-if="sidebarOpen"
                    class="flex min-w-0 items-baseline gap-2"
                >
                    <span class="truncate font-display text-lg font-semibold"
                        >Date Night</span
                    >
                    <span class="eyebrow">Admin</span>
                </span>
            </div>

            <!-- Nav -->
            <nav
                class="flex-1 space-y-0.5 overflow-y-auto p-2"
                aria-label="Admin"
            >
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    :title="sidebarOpen ? undefined : item.label"
                    :aria-label="sidebarOpen ? undefined : item.label"
                    :aria-current="
                        isActive(item.href, item.exact) ? 'page' : undefined
                    "
                    :class="[
                        'flex h-10 items-center gap-3 rounded-xl px-3 text-sm transition-colors outline-none focus-visible:ring-2 focus-visible:ring-primary',
                        isActive(item.href, item.exact)
                            ? 'bg-accent font-medium text-primary'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    ]"
                >
                    <component
                        :is="item.icon"
                        class="size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <span v-if="sidebarOpen" class="truncate">{{
                        item.label
                    }}</span>
                </Link>
            </nav>

            <!-- User -->
            <div class="border-t border-border p-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-primary-foreground gradient-primary"
                    >
                        {{ user?.name?.charAt(0) }}
                    </div>
                    <div v-if="sidebarOpen" class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{ user?.name }}
                        </p>
                        <Link
                            href="/dashboard"
                            class="inline-flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-primary"
                        >
                            <ArrowLeft class="size-3" aria-hidden="true" />
                            Back to app
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top bar -->
            <header
                class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-border bg-background/75 px-6 backdrop-blur-xl"
            >
                <button
                    type="button"
                    @click="toggleSidebar"
                    class="flex size-9 items-center justify-center rounded-full text-muted-foreground transition-colors outline-none hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary"
                    :aria-label="
                        sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'
                    "
                    :aria-expanded="sidebarOpen"
                >
                    <Menu class="size-5" aria-hidden="true" />
                </button>

                <div class="flex-1" />

                <span class="badge-soft">Admin portal</span>
            </header>

            <!-- Flash messages -->
            <div
                v-if="flash?.success || flash?.error"
                class="mx-auto w-full max-w-[1280px] space-y-2 px-6 pt-4 lg:px-8"
            >
                <div
                    v-if="flash?.success"
                    role="status"
                    class="flex items-start gap-2 rounded-xl border border-success/25 bg-success/10 px-4 py-3 text-sm text-foreground"
                >
                    <CircleCheck
                        class="mt-0.5 size-4 shrink-0 text-success"
                        aria-hidden="true"
                    />
                    {{ flash.success }}
                </div>
                <div
                    v-if="flash?.error"
                    role="alert"
                    class="flex items-start gap-2 rounded-xl border border-destructive/25 bg-destructive/10 px-4 py-3 text-sm text-foreground"
                >
                    <CircleAlert
                        class="mt-0.5 size-4 shrink-0 text-destructive"
                        aria-hidden="true"
                    />
                    {{ flash.error }}
                </div>
            </div>

            <!-- Page content -->
            <main class="mx-auto w-full max-w-[1280px] flex-1 p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
