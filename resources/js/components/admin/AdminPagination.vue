<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{ url: string | null; label: string; active: boolean }>;
}>();
</script>

<template>
    <nav class="mt-4 flex flex-wrap gap-1" aria-label="Pagination">
        <Link
            v-for="link in links"
            :key="link.label"
            :href="link.url ?? '#'"
            :aria-current="link.active ? 'page' : undefined"
            :class="[
                'inline-flex h-9 min-w-9 items-center justify-center rounded-full px-3 text-sm font-medium tabular-nums transition-colors',
                link.active
                    ? 'bg-foreground text-background'
                    : 'border border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground',
                !link.url ? 'pointer-events-none opacity-40' : '',
            ]"
        >
            <span v-html="link.label" />
        </Link>
    </nav>
</template>
