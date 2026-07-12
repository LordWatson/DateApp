<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Heart, LayoutGrid, Sparkles, Users } from '@lucide/vue';
import AnimatedBackground from '@/components/AnimatedBackground.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import FloatingHearts from '@/components/FloatingHearts.vue';
import { dashboard } from '@/routes';

interface NavItem {
    label: string;
    href: string;
    icon: unknown;
    emoji?: string;
}

const navItems: NavItem[] = [
    { label: 'Home', href: dashboard(), icon: LayoutGrid },
    { label: 'Tonight', href: dashboard(), icon: Heart, emoji: '❤️' },
    { label: 'Match', href: dashboard(), icon: Sparkles },
    { label: 'Partner', href: dashboard(), icon: Users },
];

const page = usePage();

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
                <Link :href="dashboard()" class="flex items-center gap-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl shadow-sm gradient-primary"
                    >
                        <AppLogoIcon class="size-4 text-white" />
                    </div>
                    <span class="gradient-text text-sm font-semibold"
                        >Date Night</span
                    >
                </Link>

                <slot name="header-actions" />
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
                    <span
                        v-if="item.emoji && isActive(item.href)"
                        class="text-xl leading-none"
                        >{{ item.emoji }}</span
                    >
                    <component
                        :is="item.icon"
                        v-else
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
