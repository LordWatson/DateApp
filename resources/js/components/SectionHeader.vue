<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

interface Props {
    title: string;
    actionLabel?: string;
    actionHref?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex items-baseline justify-between gap-4">
        <h2 class="text-lg leading-tight font-semibold text-foreground">
            {{ title }}
        </h2>

        <slot>
            <Link
                v-if="actionLabel && actionHref"
                :href="actionHref"
                class="group inline-flex shrink-0 items-center gap-1 text-[13px] font-medium text-primary"
            >
                {{ actionLabel }}
                <ArrowRight
                    class="size-3.5 transition-transform group-hover:translate-x-0.5"
                    aria-hidden="true"
                />
            </Link>
        </slot>
    </div>
</template>
