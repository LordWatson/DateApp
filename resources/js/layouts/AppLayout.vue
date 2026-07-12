<script setup lang="ts">
import MobileLayout from '@/layouts/MobileLayout.vue';
</script>

<template>
    <MobileLayout>
        <template #header-actions>
            <slot name="header-actions" />
        </template>
        <slot />
    </MobileLayout>
</template>
