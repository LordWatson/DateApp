<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Heart, LayoutGrid, LogOut, Sparkles, User, Users } from '@lucide/vue';
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import PartnerController from '@/actions/App/Http/Controllers/PartnerController';
import AnimatedBackground from '@/components/AnimatedBackground.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import { logout } from '@/routes';

interface NavItem {
    label: string;
    href: string;
    icon: unknown;
}

const navItems: NavItem[] = [
    { label: 'Home', href: DashboardController.index().url, icon: LayoutGrid },
    { label: 'Tonight', href: DashboardController.index().url, icon: Heart },
    { label: 'Challenges', href: DashboardController.index().url, icon: Sparkles },
    { label: 'Partner', href: PartnerController.index().url, icon: Users },
    { label: 'Profile', href: '/settings/profile', icon: User },
];

const page = usePage();

function handleLogout(): void {
    router.post(logout().url);
}

function isActive(href: string): boolean {
    return page.url === href || page.url.startsWith(href + '/');
}
</script>

<template>
    <div class="relative flex min-h-svh flex-col bg-background">
        <AnimatedBackground variant="hearts" />
        <FloatingHearts />

        <header
            class="sticky top-0 z-40 border-b border-border/50 bg-background/80 backdrop-blur-md"
        >
            <div class="flex h-14 items-center justify-between px-4">
                <Link :href="DashboardController.index().url" class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl shadow-sm gradient-primary"
                    >
                        <AppLogoIcon class="size-4 text-white" />
                    </div>
                    <span class="gradient-text text-sm font-semibold"
                        >Date Night</span
                    >
                </Link>

                <slot name="header-actions">
                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-2xl text-muted-foreground transition-all duration-200 hover:bg-primary/10 hover:text-primary active:scale-95"
                        aria-label="Log out"
                        @click="handleLogout"
                    >
                        <LogOut class="size-5" aria-hidden="true" />
                    </button>
                </slot>
            </div>
        </header>

        <main class="animate-page-fade-in flex-1">
            <slot />
        </main>

        <nav
            class="sticky bottom-0 z-40 border-t border-border/50 bg-background/90 backdrop-blur-md"
            aria-label="Main navigation"
        >
            <div class="flex items-center justify-around px-2 py-2">
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    :class="[
                        'flex flex-col items-center gap-1 rounded-2xl px-4 py-2 transition-all duration-200',
                        isActive(item.href)
                            ? 'text-primary'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    :aria-current="isActive(item.href) ? 'page' : undefined"
                >
                    <component
                        :is="item.icon"
                        :class="[
                            'size-5',
                            isActive(item.href)
                                ? 'text-primary'
                                : 'text-muted-foreground',
                        ]"
                        aria-hidden="true"
                    />
                    <span class="text-xs font-medium">{{ item.label }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
