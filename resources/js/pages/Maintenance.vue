<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Wrench } from '@lucide/vue';
import TextLink from '@/components/TextLink.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { login, logout } from '@/routes';

defineOptions({ layout: GuestLayout });

const page = usePage();
</script>

<template>
    <Head title="Back soon" />

    <div class="flex flex-col items-center gap-4 text-center">
        <div class="icon-tile h-14 w-14 text-primary" aria-hidden="true">
            <Wrench class="size-6" />
        </div>
        <h1 class="text-[1.75rem] leading-[1.15] font-semibold text-foreground">
            Back soon
        </h1>
        <p class="text-sm leading-relaxed text-pretty text-muted-foreground">
            Date Night is down for a little maintenance. Your plans, notes and
            moments are safe. Please try again in a while.
        </p>

        <TextLink
            v-if="page.props.auth?.user"
            :href="logout()"
            as="button"
            class="text-sm"
        >
            Log out
        </TextLink>
        <p v-else class="text-sm text-muted-foreground">
            Staff?
            <TextLink :href="login()">Sign in</TextLink>
        </p>
    </div>
</template>
