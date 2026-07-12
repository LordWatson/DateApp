<script setup lang="ts">
import GuestLayout from '@/layouts/GuestLayout.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <GuestLayout :title="title" :description="description">
        <slot />
    </GuestLayout>
</template>
