<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { create } from '@/routes/admin/questionnaires';
import { edit } from '@/routes/admin/questionnaires';

defineOptions({ layout: AdminLayout });

defineProps<{
    questionnaires: {
        data: Array<{
            id: number;
            title: string;
            emoji: string | null;
            status: string;
            visibility: string;
            questions_count: number;
            responses_count: number;
            display_order: number;
            is_seasonal: boolean;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: { search?: string; status?: string };
}>();

const search = ref('');
const status = ref('');

const applyFilters = () => {
    router.get(
        route('admin.questionnaires.index'),
        { search: search.value, status: status.value },
        { preserveState: true },
    );
};

const confirmDelete = ref<number | null>(null);

const doDelete = (id: number) => {
    router.delete(route('admin.questionnaires.destroy', id), {
        onSuccess: () => {
            confirmDelete.value = null;
        },
    });
};

const doDuplicate = (id: number) => {
    router.post(route('admin.questionnaires.duplicate', id));
};

const columns = [
    { key: 'title', label: 'Questionnaire' },
    { key: 'status', label: 'Status' },
    { key: 'questions', label: 'Questions' },
    { key: 'responses', label: 'Responses' },
    { key: 'order', label: 'Order' },
    { key: 'actions', label: '' },
];
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Questionnaires</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ questionnaires.total }} total
                </p>
            </div>
            <Link
                :href="create().url"
                class="rounded-xl bg-pink-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-pink-600"
            >
                + New Questionnaire
            </Link>
        </div>

        <!-- Filters -->
        <div
            class="mb-6 flex flex-wrap gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
        >
            <input
                v-model="search"
                type="text"
                placeholder="Search questionnaires…"
                class="min-w-48 flex-1 rounded-xl border border-gray-200 px-4 py-2 text-sm focus:ring-2 focus:ring-pink-300 focus:outline-none"
                @keyup.enter="applyFilters"
            />
            <select
                v-model="status"
                class="rounded-xl border border-gray-200 px-4 py-2 text-sm focus:ring-2 focus:ring-pink-300 focus:outline-none"
                @change="applyFilters"
            >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
            </select>
            <button
                @click="applyFilters"
                class="rounded-xl bg-pink-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-pink-600"
            >
                Search
            </button>
        </div>

        <AdminTable :columns="columns" empty="No questionnaires found.">
            <tr
                v-for="q in questionnaires.data"
                :key="q.id"
                class="border-b border-gray-50 transition-colors hover:bg-gray-50"
            >
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span v-if="q.emoji" class="text-xl">{{
                            q.emoji
                        }}</span>
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ q.title }}
                            </p>
                            <span
                                v-if="q.is_seasonal"
                                class="text-xs text-purple-500"
                                >🌸 Seasonal</span
                            >
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span
                        :class="[
                            'rounded-full px-2 py-1 text-xs font-medium capitalize',
                            q.status === 'active'
                                ? 'bg-green-100 text-green-700'
                                : q.status === 'draft'
                                  ? 'bg-yellow-100 text-yellow-700'
                                  : 'bg-gray-100 text-gray-600',
                        ]"
                    >
                        {{ q.status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                    {{ q.questions_count }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                    {{ q.responses_count }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600">
                    {{ q.display_order }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <Link
                            :href="edit(q.id).url"
                            class="text-xs text-pink-500 hover:underline"
                            >Edit</Link
                        >
                        <button
                            @click="doDuplicate(q.id)"
                            class="text-xs text-blue-500 hover:underline"
                        >
                            Duplicate
                        </button>
                        <button
                            @click="confirmDelete = q.id"
                            class="text-xs text-red-500 hover:underline"
                        >
                            Delete
                        </button>
                    </div>
                </td>
            </tr>
        </AdminTable>

        <AdminPagination :links="questionnaires.links" />

        <!-- Delete Modal -->
        <div
            v-if="confirmDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="mb-2 font-bold text-gray-900">
                    Delete Questionnaire?
                </h3>
                <p class="mb-6 text-sm text-gray-500">
                    This will permanently delete the questionnaire and all its
                    questions.
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
