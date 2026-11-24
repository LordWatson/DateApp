<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { logout } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="space-y-6 px-4 pt-6 pb-28">
        <PageHeader
            title="Settings"
            eyebrow="Account"
            description="Manage your profile and account settings"
        />

        <nav
            class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1"
            aria-label="Settings"
        >
            <Link
                v-for="item in sidebarNavItems"
                :key="toUrl(item.href)"
                :href="item.href"
                class="chip focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                :data-active="isCurrentOrParentUrl(item.href)"
                :aria-current="
                    isCurrentOrParentUrl(item.href) ? 'page' : undefined
                "
            >
                {{ item.title }}
            </Link>
        </nav>

        <section class="space-y-4">
            <slot />
        </section>

        <button
            type="button"
            class="flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-border bg-card text-sm font-medium text-muted-foreground transition-colors hover:border-foreground/30 hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
            @click="router.post(logout().url)"
        >
            <LogOut class="size-4" aria-hidden="true" />
            Log out
        </button>
    </div>
</template>
