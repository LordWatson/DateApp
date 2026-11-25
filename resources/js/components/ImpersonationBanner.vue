<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { computed, ref } from 'vue';
import { stop } from '@/routes/impersonation';

const page = usePage();
const impersonating = computed(() => page.props.impersonating);
const stopping = ref(false);

function stopViewing(): void {
    router.post(
        stop().url,
        {},
        {
            onStart: () => (stopping.value = true),
            onFinish: () => (stopping.value = false),
        },
    );
}
</script>

<template>
    <div
        v-if="impersonating"
        class="sticky top-0 z-50 flex items-center justify-center gap-3 bg-foreground px-4 py-2 text-sm text-background"
        role="status"
    >
        <Eye class="size-4 shrink-0" aria-hidden="true" />
        <span class="min-w-0 truncate"
            >Viewing as {{ impersonating.name }} (read-only)</span
        >
        <button
            type="button"
            class="shrink-0 rounded-full bg-background px-3 py-1 text-xs font-semibold text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none disabled:opacity-60"
            :disabled="stopping"
            @click="stopViewing"
        >
            Stop
        </button>
    </div>
</template>
