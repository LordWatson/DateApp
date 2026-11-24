<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from '@lucide/vue';

interface Props {
    title: string;
    eyebrow?: string;
    description?: string;
    backHref?: string;
    backLabel?: string;
    align?: 'left' | 'center';
}

const {
    title,
    eyebrow,
    description,
    backHref,
    backLabel = 'Back',
    align = 'left',
} = defineProps<Props>();
</script>

<template>
    <header :class="align === 'center' && 'text-center'">
        <Link
            v-if="backHref"
            :href="backHref"
            class="mb-4 -ml-1.5 inline-flex min-h-9 items-center gap-1 rounded-full pr-3 pl-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
        >
            <ChevronLeft class="size-4" aria-hidden="true" />
            {{ backLabel }}
        </Link>

        <div
            :class="[
                'flex items-end gap-4',
                align === 'center' ? 'justify-center' : 'justify-between',
            ]"
        >
            <div class="min-w-0 space-y-1.5">
                <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
                <h1
                    class="text-[1.75rem] leading-[1.15] font-semibold text-foreground"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="description || $slots.description"
                    class="text-sm leading-relaxed text-muted-foreground"
                >
                    <slot name="description">{{ description }}</slot>
                </p>
            </div>

            <div v-if="$slots.default" class="shrink-0">
                <slot />
            </div>
        </div>
    </header>
</template>
