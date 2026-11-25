<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { privacy, terms } from '@/routes';
</script>

<template>
    <nav
        aria-label="Legal"
        class="flex items-center justify-center gap-4 text-xs text-muted-foreground"
    >
        <Link
            :href="privacy()"
            class="underline-offset-4 hover:text-foreground hover:underline"
            >Privacy</Link
        >
        <Link
            :href="terms()"
            class="underline-offset-4 hover:text-foreground hover:underline"
            >Terms</Link
        >
    </nav>
</template>
