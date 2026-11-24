<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Plus, X } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
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
                <h1 class="text-2xl font-semibold text-foreground">
                    Questionnaires
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ questionnaires.total }} total
                </p>
            </div>
            <Button as-child size="sm"
                ><Link :href="create().url">
                    <Plus class="size-4" aria-hidden="true" />New questionnaire
                </Link></Button
            >
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-3 card-premium rounded-2xl p-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search questionnaires…"
                class="input-premium min-w-48 flex-1 px-3 py-2 text-sm"
                @keyup.enter="applyFilters"
                aria-label="Search questionnaires"
            />
            <select
                v-model="status"
                class="input-premium w-auto px-3 py-2 text-sm"
                @change="applyFilters"
                aria-label="Status"
            >
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
            </select>
            <Button size="sm" @click="applyFilters"> Search </Button>
        </div>

        <AdminTable :columns="columns" empty="No questionnaires found.">
            <tr
                v-for="q in questionnaires.data"
                :key="q.id"
                class="transition-colors hover:bg-muted/60"
            >
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span v-if="q.emoji" class="text-xl">{{
                            q.emoji
                        }}</span>
                        <div>
                            <p class="font-medium text-foreground">
                                {{ q.title }}
                            </p>
                            <span v-if="q.is_seasonal" class="badge-soft"
                                >Seasonal</span
                            >
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span
                        :class="[
                            'badge-soft capitalize',
                            q.status === 'active'
                                ? 'bg-success/10 text-success'
                                : q.status === 'draft'
                                  ? 'bg-warning/10 text-warning'
                                  : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        {{ q.status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ q.questions_count }}
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ q.responses_count }}
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ q.display_order }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <Link
                            :href="edit(q.id).url"
                            class="text-xs font-medium text-foreground hover:underline"
                            >Edit</Link
                        >
                        <button
                            @click="doDuplicate(q.id)"
                            class="text-xs text-primary hover:underline"
                        >
                            Duplicate
                        </button>
                        <button
                            @click="confirmDelete = q.id"
                            class="text-xs text-destructive hover:underline"
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
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md card-premium p-6"
                role="dialog"
                aria-modal="true"
            >
                <div class="mb-1 flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-foreground">
                        Delete questionnaire?
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
                    This will permanently delete the questionnaire and all its
                    questions.
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
