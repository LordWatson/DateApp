<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

defineProps<{
    templates: Array<{
        id: number;
        key: string;
        label: string;
        subject: string;
        active: boolean;
        updated_at: string;
    }>;
}>();
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">
                Email templates
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Manage transactional email templates.
            </p>
        </div>

        <div class="divide-y divide-border card-premium rounded-2xl">
            <div
                v-for="template in templates"
                :key="template.id"
                class="flex items-center justify-between px-6 py-4"
            >
                <div class="min-w-0 flex-1">
                    <div class="mb-1 flex items-center gap-2">
                        <p class="text-sm font-medium text-foreground">
                            {{ template.label }}
                        </p>
                        <span
                            :class="[
                                'badge-soft',
                                template.active
                                    ? 'bg-success/10 text-success'
                                    : 'bg-muted text-muted-foreground',
                            ]"
                        >
                            {{ template.active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Subject: {{ template.subject }}
                    </p>
                    <p class="mt-0.5 font-mono text-xs text-muted-foreground">
                        {{ template.key }}
                    </p>
                </div>
                <div class="ml-4 flex items-center gap-3">
                    <span class="text-xs text-muted-foreground">{{
                        new Date(template.updated_at).toLocaleDateString()
                    }}</span>
                    <Link
                        :href="admin.emailTemplates.edit(template.id).url"
                        class="text-sm font-medium text-foreground hover:underline"
                    >
                        Edit
                    </Link>
                </div>
            </div>
            <p
                v-if="!templates.length"
                class="px-6 py-12 text-center text-sm text-muted-foreground"
            >
                No email templates found.
            </p>
        </div>
    </div>
</template>
