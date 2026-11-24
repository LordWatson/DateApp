<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps<{
    flags: Record<
        string,
        Array<{
            id: number;
            key: string;
            label: string;
            description: string | null;
            enabled: boolean;
            group: string;
        }>
    >;
}>();

const toggle = (id: number, enabled: boolean) => {
    router.put(route('admin.feature-flags.update', id), { enabled: !enabled });
};
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">
                Feature flags
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Enable or disable application features without code changes.
            </p>
        </div>

        <div
            v-for="(groupFlags, groupName) in flags"
            :key="groupName"
            class="mb-8"
        >
            <h2 class="mb-3 font-sans eyebrow">{{ groupName }}</h2>
            <div class="divide-y divide-border card-premium rounded-2xl">
                <div
                    v-for="flag in groupFlags"
                    :key="flag.id"
                    class="flex items-center justify-between px-6 py-4"
                >
                    <div class="mr-4 min-w-0 flex-1">
                        <p class="text-sm font-medium text-foreground">
                            {{ flag.label }}
                        </p>
                        <p
                            v-if="flag.description"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ flag.description }}
                        </p>
                        <p
                            class="mt-0.5 font-mono text-xs text-muted-foreground"
                        >
                            {{ flag.key }}
                        </p>
                    </div>
                    <button
                        @click="toggle(flag.id, flag.enabled)"
                        type="button"
                        role="switch"
                        :aria-checked="flag.enabled"
                        :aria-label="flag.label"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                            flag.enabled ? 'bg-primary' : 'bg-input',
                        ]"
                    >
                        <span
                            :class="[
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-primary-foreground shadow-sm ring-0 transition duration-200 ease-in-out',
                                flag.enabled
                                    ? 'translate-x-5'
                                    : 'translate-x-0',
                            ]"
                        />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
