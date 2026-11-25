<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import TextLink from '@/components/TextLink.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { dashboard } from '@/routes';

// Rendered from the exception handler without shared props, so nothing here may read `auth`.
defineOptions({ layout: GuestLayout });

const props = defineProps<{ status: number }>();

const messages: Record<number, { title: string; body: string }> = {
    403: {
        title: 'Not yours to open',
        body: 'You do not have access to this page. If you followed a link, it may belong to another account.',
    },
    404: {
        title: 'Page not found',
        body: 'This page does not exist, or it has been moved or deleted.',
    },
    503: {
        title: 'Back soon',
        body: 'Date Night is down for a little maintenance. Your plans, notes and moments are safe. Please try again in a while.',
    },
};

const message = computed(
    () =>
        messages[props.status] ?? {
            title: 'Something went wrong',
            body: 'That was our fault, not yours. Please try again in a moment.',
        },
);
</script>

<template>
    <Head :title="message.title" />

    <div class="flex flex-col items-center gap-4 text-center">
        <p class="eyebrow">Error {{ status }}</p>
        <h1 class="text-[1.75rem] leading-[1.15] font-semibold text-foreground">
            {{ message.title }}
        </h1>
        <p class="text-sm leading-relaxed text-pretty text-muted-foreground">
            {{ message.body }}
        </p>

        <!-- Guests are sent on to the login page. -->
        <TextLink :href="dashboard()" class="text-sm">
            Back to Date Night
        </TextLink>
    </div>
</template>
