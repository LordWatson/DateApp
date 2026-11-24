<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps<{
    logs: {
        data: Array<{
            id: number;
            action: string;
            auditable_type: string | null;
            auditable_id: number | null;
            ip_address: string | null;
            created_at: string;
            user: { id: number; name: string; email: string } | null;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: { search?: string; action?: string };
}>();

const search = ref('');
const action = ref('');

const applyFilters = () => {
    router.get(
        route('admin.audit-logs.index'),
        { search: search.value, action: action.value },
        { preserveState: true },
    );
};

const columns = [
    { key: 'user', label: 'User' },
    { key: 'action', label: 'Action' },
    { key: 'target', label: 'Target' },
    { key: 'ip', label: 'IP address' },
    { key: 'time', label: 'Time' },
];
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground">Audit logs</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ logs.total }} total log entries
            </p>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-3 card-premium rounded-2xl p-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search by action or user…"
                class="input-premium min-w-48 flex-1 px-3 py-2 text-sm"
                @keyup.enter="applyFilters"
                aria-label="Search by action or user"
            />
            <input
                v-model="action"
                type="text"
                placeholder="Filter by action…"
                class="input-premium w-auto px-3 py-2 text-sm"
                @keyup.enter="applyFilters"
                aria-label="Filter by action"
            />
            <Button size="sm" @click="applyFilters"> Search </Button>
        </div>

        <AdminTable :columns="columns" empty="No audit logs found.">
            <tr
                v-for="log in logs.data"
                :key="log.id"
                class="transition-colors hover:bg-muted/60"
            >
                <td class="px-4 py-3">
                    <div v-if="log.user">
                        <p class="text-sm font-medium text-foreground">
                            {{ log.user.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ log.user.email }}
                        </p>
                    </div>
                    <span v-else class="text-xs text-muted-foreground"
                        >System</span
                    >
                </td>
                <td class="px-4 py-3">
                    <span
                        class="rounded-lg bg-muted px-2 py-1 font-mono text-xs text-foreground"
                        >{{ log.action }}</span
                    >
                </td>
                <td class="px-4 py-3 text-xs text-muted-foreground">
                    <span v-if="log.auditable_type">
                        {{ log.auditable_type.split('\\').pop() }} #{{
                            log.auditable_id
                        }}
                    </span>
                    <span v-else>—</span>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                    {{ log.ip_address ?? '—' }}
                </td>
                <td class="px-4 py-3 text-xs text-muted-foreground">
                    {{ new Date(log.created_at).toLocaleString() }}
                </td>
            </tr>
        </AdminTable>

        <AdminPagination :links="logs.links" />
    </div>
</template>
