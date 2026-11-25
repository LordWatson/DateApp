<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    BookHeart,
    LayoutGrid,
    Search,
    Sparkles,
    Trophy,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import AchievementController from '@/actions/App/Http/Controllers/AchievementController';
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import QuestionnaireController from '@/actions/App/Http/Controllers/QuestionnaireController';
import RelationshipHubController from '@/actions/App/Http/Controllers/RelationshipHubController';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';
import { Toaster } from '@/components/ui/sonner';
import { index as searchIndex } from '@/routes/search';

interface NavItem {
    label: string;
    href: string;
    icon: unknown;
}

const navItems: NavItem[] = [
    { label: 'Home', href: DashboardController.index().url, icon: LayoutGrid },
    {
        label: 'Tonight',
        href: QuestionnaireController.index().url,
        icon: BookHeart,
    },
    {
        label: 'Hub',
        href: RelationshipHubController.index().url,
        icon: Sparkles,
    },
    { label: 'Wins', href: AchievementController.index().url, icon: Trophy },
    { label: 'Profile', href: '/settings/profile', icon: User },
];

const page = usePage();

const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);

function isActive(href: string): boolean {
    return page.url === href || page.url.startsWith(href + '/');
}
</script>

<template>
    <div class="relative flex min-h-svh flex-col">
        <ImpersonationBanner />
        <header
            class="sticky top-0 z-40 border-b border-border/60 bg-background/75 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-xl items-center justify-between px-4"
            >
                <Link
                    :href="DashboardController.index().url"
                    class="flex items-center gap-2.5"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl shadow-glow gradient-primary"
                    >
                        <AppLogoIcon class="size-4.5 text-white" />
                    </div>
                    <span
                        class="font-display text-lg font-semibold tracking-tight text-foreground"
                        >Date Night</span
                    >
                </Link>

                <slot name="header-actions">
                    <div class="flex items-center gap-1">
                        <Link
                            :href="searchIndex().url"
                            class="flex h-10 w-10 items-center justify-center rounded-full text-muted-foreground transition-all duration-200 hover:bg-accent hover:text-primary active:scale-95"
                            aria-label="Search"
                        >
                            <Search class="size-5" aria-hidden="true" />
                        </Link>
                        <Link
                            :href="NotificationController.index().url"
                            class="relative flex h-10 w-10 items-center justify-center rounded-full text-muted-foreground transition-all duration-200 hover:bg-accent hover:text-primary active:scale-95"
                            aria-label="Notifications"
                        >
                            <Bell class="size-5" aria-hidden="true" />
                            <span
                                v-if="unreadCount > 0"
                                class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-white ring-2 ring-background"
                                >{{
                                    unreadCount > 9 ? '9+' : unreadCount
                                }}</span
                            >
                        </Link>
                    </div>
                </slot>
            </div>
        </header>

        <main class="animate-page-fade-in mx-auto w-full max-w-xl flex-1">
            <slot />
        </main>

        <nav
            class="sticky z-40 mx-auto w-[calc(100%-1.5rem)] max-w-md rounded-full border border-border/70 bg-card/85 shadow-xl backdrop-blur-xl"
            aria-label="Main navigation"
            style="bottom: calc(env(safe-area-inset-bottom, 0px) + 0.75rem)"
        >
            <div class="flex items-center justify-between p-1.5">
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    :class="[
                        'flex min-w-14 flex-1 flex-col items-center gap-0.5 rounded-full px-2 py-2 transition-all duration-200 active:scale-95',
                        isActive(item.href)
                            ? 'bg-accent text-primary'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    :aria-current="isActive(item.href) ? 'page' : undefined"
                >
                    <component
                        :is="item.icon"
                        class="size-5"
                        :stroke-width="isActive(item.href) ? 2.25 : 1.75"
                        aria-hidden="true"
                    />
                    <span class="text-[11px] font-medium">{{
                        item.label
                    }}</span>
                </Link>
            </div>
        </nav>

        <Toaster position="top-center" />
    </div>
</template>
