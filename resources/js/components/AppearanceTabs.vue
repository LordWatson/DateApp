<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div class="flex rounded-full border border-border bg-muted/70 p-1">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            :aria-pressed="appearance === value"
            @click="updateAppearance(value)"
            :class="[
                'flex min-h-10 flex-1 items-center justify-center gap-1.5 rounded-full px-3 text-[13px] font-medium transition-all duration-200 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none',
                appearance === value
                    ? 'bg-card text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="size-4" aria-hidden="true" />
            {{ label }}
        </button>
    </div>
</template>
