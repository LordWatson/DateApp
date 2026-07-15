<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
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
    suspendForm.post(route('admin.users.suspend', id), {
        onSuccess: () => {
            suspendUserId.value = null;
            suspendReason.value = '';
        },
    });
};

const doDelete = (id: number) => {
    router.delete(route('admin.users.destroy', id), {
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
                <h1 class="text-2xl font-bold text-gray-900">Users</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ users.total }} total users
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="mb-6 flex flex-wrap gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
        >
            <input
                v-model="search"
                type="text"
                placeholder="Search by name or email…"
                class="min-w-48 flex-1 rounded-xl border border-gray-200 px-4 py-2 text-sm focus:ring-2 focus:ring-pink-300 focus:outline-none"
                @keyup.enter="applyFilters"
            />
            <select
                v-model="filter"
                class="rounded-xl border border-gray-200 px-4 py-2 text-sm focus:ring-2 focus:ring-pink-300 focus:outline-none"
                @change="applyFilters"
            >
                <option value="">All Users</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="connected">Connected</option>
                <option value="waiting">Waiting for Partner</option>
                <option value="admins">Admins</option>
            </select>
            <button
                @click="applyFilters"
                class="rounded-xl bg-pink-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-pink-600"
            >
                Search
            </button>
        </div>

        <!-- Table -->
        <AdminTable :columns="columns" empty="No users found.">
            <tr
                v-for="user in users.data"
                :key="user.id"
                class="border-b border-gray-50 transition-colors hover:bg-gray-50"
            >
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-400 to-purple-500 text-xs font-bold text-white"
                        >
                            {{ user.name.charAt(0) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ user.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span
                        v-if="user.role"
                        class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-700"
                    >
                        {{ user.role.label }}
                    </span>
                    <span v-else class="text-xs text-gray-400">User</span>
                </td>
                <td class="px-4 py-3">
                    <span
                        :class="[
                            'rounded-full px-2 py-1 text-xs font-medium',
                            user.is_suspended
                                ? 'bg-red-100 text-red-700'
                                : 'bg-green-100 text-green-700',
                        ]"
                    >
                        {{ user.is_suspended ? 'Suspended' : 'Active' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                    {{ user.partner_id ? '✅ Connected' : '⏳ Waiting' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                    {{ user.responses_count }}
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">
                    {{ new Date(user.created_at).toLocaleDateString() }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <Link
                            :href="show(user.id).url"
                            class="text-xs text-pink-500 hover:underline"
                            >View</Link
                        >
                        <button
                            v-if="!user.is_suspended"
                            @click="suspendUserId = user.id"
                            class="text-xs text-yellow-600 hover:underline"
                        >
                            Suspend
                        </button>
                        <button
                            v-else
                            @click="
                                router.post(
                                    route('admin.users.unsuspend', user.id),
                                )
                            "
                            class="text-xs text-green-600 hover:underline"
                        >
                            Unsuspend
                        </button>
                        <button
                            @click="confirmDelete = user.id"
                            class="text-xs text-red-500 hover:underline"
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
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 font-bold text-gray-900">Suspend User</h3>
                <textarea
                    v-model="suspendReason"
                    placeholder="Reason for suspension (optional)…"
                    class="mb-4 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:ring-2 focus:ring-pink-300 focus:outline-none"
                    rows="3"
                />
                <div class="flex gap-3">
                    <button
                        @click="doSuspend(suspendUserId!)"
                        class="flex-1 rounded-xl bg-yellow-500 py-2 text-sm font-medium text-white transition-colors hover:bg-yellow-600"
                    >
                        Suspend
                    </button>
                    <button
                        @click="suspendUserId = null"
                        class="flex-1 rounded-xl bg-gray-100 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirm Modal -->
        <div
            v-if="confirmDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-2 font-bold text-gray-900">Delete User?</h3>
                <p class="mb-6 text-sm text-gray-500">
                    This action cannot be undone. All user data will be
                    permanently deleted.
                </p>
                <div class="flex gap-3">
                    <button
                        @click="doDelete(confirmDelete!)"
                        class="flex-1 rounded-xl bg-red-500 py-2 text-sm font-medium text-white transition-colors hover:bg-red-600"
                    >
                        Delete
                    </button>
                    <button
                        @click="confirmDelete = null"
                        class="flex-1 rounded-xl bg-gray-100 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
