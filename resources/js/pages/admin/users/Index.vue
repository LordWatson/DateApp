<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { ref } from 'vue';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';
import { index } from '@/routes/admin/users';
import { show } from '@/routes/admin/users';

defineOptions({ layout: AdminLayout });

defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            is_suspended: boolean;
            onboarding_completed: boolean;
            partner_id: number | null;
            created_at: string;
            role: { label: string } | null;
            responses_count: number;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    roles: Array<{ id: number; name: string; label: string }>;
    filters: { search?: string; filter?: string };
}>();

const search = ref('');
const filter = ref('');

const applyFilters = () => {
    router.get(
        index().url,
        { search: search.value, filter: filter.value },
        { preserveState: true },
    );
};

const confirmDelete = ref<number | null>(null);
const suspendUserId = ref<number | null>(null);
const suspendReason = ref('');

const suspendForm = useForm({ reason: '' });

const doSuspend = (id: number) => {
    suspendForm.reason = suspendReason.value;
    suspendForm.post(admin.users.suspend(id).url, {
        onSuccess: () => {
            suspendUserId.value = null;
            suspendReason.value = '';
        },
    });
};

const doDelete = (id: number) => {
    router.delete(admin.users.destroy(id).url, {
        onSuccess: () => {
            confirmDelete.value = null;
        },
    });
};

const columns = [
    { key: 'name', label: 'User' },
    { key: 'role', label: 'Role' },
    { key: 'status', label: 'Status' },
    { key: 'partner', label: 'Partner' },
    { key: 'responses', label: 'Responses' },
    { key: 'joined', label: 'Joined' },
    { key: 'actions', label: '' },
];
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">Users</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ users.total }} total users
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-3 card-premium rounded-2xl p-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search by name or email…"
                class="input-premium min-w-48 flex-1 px-3 py-2 text-sm"
                @keyup.enter="applyFilters"
                aria-label="Search by name or email"
            />
            <select
                v-model="filter"
                class="input-premium w-auto px-3 py-2 text-sm"
                @change="applyFilters"
                aria-label="Filter"
            >
                <option value="">All users</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="connected">Connected</option>
                <option value="waiting">Waiting for partner</option>
                <option value="admins">Admins</option>
            </select>
            <Button size="sm" @click="applyFilters"> Search </Button>
        </div>

        <!-- Table -->
        <AdminTable :columns="columns" empty="No users found.">
            <tr
                v-for="user in users.data"
                :key="user.id"
                class="transition-colors hover:bg-muted/60"
            >
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-xs font-semibold text-primary-foreground gradient-primary"
                        >
                            {{ user.name.charAt(0) }}
                        </div>
                        <div>
                            <p class="font-medium text-foreground">
                                {{ user.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span
                        v-if="user.role"
                        class="badge-soft bg-accent text-primary"
                    >
                        {{ user.role.label }}
                    </span>
                    <span v-else class="text-xs text-muted-foreground"
                        >User</span
                    >
                </td>
                <td class="px-4 py-3">
                    <span
                        :class="[
                            'badge-soft',
                            user.is_suspended
                                ? 'bg-destructive/10 text-destructive'
                                : 'bg-success/10 text-success',
                        ]"
                    >
                        {{ user.is_suspended ? 'Suspended' : 'Active' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ user.partner_id ? 'Connected' : 'Waiting' }}
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ user.responses_count }}
                </td>
                <td class="px-4 py-3 text-xs text-muted-foreground">
                    {{ new Date(user.created_at).toLocaleDateString() }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <Link
                            :href="show(user.id).url"
                            class="text-xs font-medium text-foreground hover:underline"
                            >View</Link
                        >
                        <button
                            v-if="!user.is_suspended"
                            @click="suspendUserId = user.id"
                            class="text-xs text-warning hover:underline"
                        >
                            Suspend
                        </button>
                        <button
                            v-else
                            @click="
                                router.post(admin.users.unsuspend(user.id).url)
                            "
                            class="text-xs text-success hover:underline"
                        >
                            Unsuspend
                        </button>
                        <button
                            @click="confirmDelete = user.id"
                            class="text-xs text-destructive hover:underline"
                        >
                            Delete
                        </button>
                    </div>
                </td>
            </tr>
        </AdminTable>

        <AdminPagination :links="users.links" />

        <!-- Suspend Modal -->
        <div
            v-if="suspendUserId"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md card-premium p-6"
                role="dialog"
                aria-modal="true"
            >
                <div class="mb-1 flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-foreground">
                        Suspend user
                    </h2>
                    <button
                        type="button"
                        class="-mt-1 -mr-1 flex size-8 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Close"
                        @click="suspendUserId = null"
                    >
                        <X class="size-4" aria-hidden="true" />
                    </button>
                </div>
                <label class="mb-4 block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Reason for suspension (optional)</span
                    ><textarea
                        v-model="suspendReason"
                        class="input-premium px-3 py-2 text-sm"
                        rows="3"
                    />
                </label>
                <div class="flex gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="doSuspend(suspendUserId!)"
                        class="flex-1 border-warning/40 text-warning"
                    >
                        Suspend
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="suspendUserId = null"
                        class="flex-1"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete Confirm Modal -->
        <div
            v-if="confirmDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md card-premium p-6"
                role="dialog"
                aria-modal="true"
            >
                <div class="mb-1 flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-foreground">
                        Delete user?
                    </h2>
                    <button
                        type="button"
                        class="-mt-1 -mr-1 flex size-8 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Close"
                        @click="confirmDelete = null"
                    >
                        <X class="size-4" aria-hidden="true" />
                    </button>
                </div>
                <p class="mb-6 text-sm text-muted-foreground">
                    This action cannot be undone. All user data will be
                    permanently deleted.
                </p>
                <div class="flex gap-3">
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="doDelete(confirmDelete!)"
                        class="flex-1"
                    >
                        Delete
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="confirmDelete = null"
                        class="flex-1"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
