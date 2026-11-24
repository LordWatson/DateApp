<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Plus, X } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps<{
    challenges: {
        data: Array<{
            id: number;
            title: string;
            emoji: string | null;
            difficulty: string;
            category: string | null;
            season: string | null;
            active: boolean;
            archived: boolean;
            weight: number;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: { search?: string; difficulty?: string; category?: string };
}>();

const search = ref('');
const difficulty = ref('');

const applyFilters = () => {
    router.get(
        route('admin.challenges.index'),
        { search: search.value, difficulty: difficulty.value },
        { preserveState: true },
    );
};

const showCreate = ref(false);
const editingId = ref<number | null>(null);
const confirmDelete = ref<number | null>(null);

const createForm = useForm({
    title: '',
    description: '',
    emoji: '',
    difficulty: 'easy',
    category: '',
    season: '',
    weight: 1,
    active: true,
    display_order: 0,
});

const editForm = useForm({
    title: '',
    description: '',
    emoji: '',
    difficulty: 'easy',
    category: '',
    season: '',
    weight: 1,
    active: true,
    display_order: 0,
});

const startEdit = (challenge: {
    id: number;
    title: string;
    emoji: string | null;
    difficulty: string;
    category: string | null;
    season: string | null;
    weight: number;
    active: boolean;
}) => {
    editingId.value = challenge.id;
    editForm.title = challenge.title;
    editForm.emoji = challenge.emoji ?? '';
    editForm.difficulty = challenge.difficulty;
    editForm.category = challenge.category ?? '';
    editForm.season = challenge.season ?? '';
    editForm.weight = challenge.weight;
    editForm.active = challenge.active;
};

const saveEdit = () => {
    editForm.put(route('admin.challenges.update', editingId.value!), {
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const doCreate = () => {
    createForm.post(route('admin.challenges.store'), {
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
};

const doDelete = (id: number) => {
    router.delete(route('admin.challenges.destroy', id), {
        onSuccess: () => {
            confirmDelete.value = null;
        },
    });
};

const doArchive = (id: number) => {
    router.post(route('admin.challenges.archive', id));
};

const columns = [
    { key: 'title', label: 'Challenge' },
    { key: 'difficulty', label: 'Difficulty' },
    { key: 'category', label: 'Category' },
    { key: 'weight', label: 'Weight' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' },
];
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Challenges
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ challenges.total }} total
                </p>
            </div>
            <Button size="sm" @click="showCreate = !showCreate">
                <Plus class="size-4" aria-hidden="true" />New challenge
            </Button>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-3 card-premium rounded-2xl p-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search challenges…"
                class="input-premium min-w-48 flex-1 px-3 py-2 text-sm"
                @keyup.enter="applyFilters"
                aria-label="Search challenges"
            />
            <select
                v-model="difficulty"
                class="input-premium w-auto px-3 py-2 text-sm"
                @change="applyFilters"
                aria-label="Difficulty"
            >
                <option value="">All difficulties</option>
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
            </select>
            <Button size="sm" @click="applyFilters"> Search </Button>
        </div>

        <!-- Create Form -->
        <div
            v-if="showCreate"
            class="mb-6 space-y-3 card-premium rounded-2xl p-5"
        >
            <h3 class="text-lg font-semibold text-foreground">New challenge</h3>
            <div class="grid grid-cols-2 gap-3">
                <label class="block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Emoji</span
                    ><input
                        v-model="createForm.emoji"
                        class="input-premium px-3 py-2 text-sm"
                /></label>
                <label class="block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Title *</span
                    ><input
                        v-model="createForm.title"
                        required
                        class="input-premium px-3 py-2 text-sm"
                /></label>
                <label class="block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Difficulty</span
                    ><select
                        v-model="createForm.difficulty"
                        class="input-premium px-3 py-2 text-sm"
                    >
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select></label
                >
                <label class="block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Category</span
                    ><input
                        v-model="createForm.category"
                        class="input-premium px-3 py-2 text-sm"
                /></label>
            </div>
            <label class="block"
                ><span
                    class="mb-1 block text-xs font-medium text-muted-foreground"
                    >Description</span
                ><textarea
                    v-model="createForm.description"
                    rows="2"
                    class="input-premium px-3 py-2 text-sm"
                />
            </label>
            <div class="flex gap-2">
                <Button
                    size="sm"
                    @click="doCreate"
                    :disabled="createForm.processing"
                    >Create</Button
                >
                <Button variant="outline" size="sm" @click="showCreate = false"
                    >Cancel</Button
                >
            </div>
        </div>

        <AdminTable :columns="columns" empty="No challenges found.">
            <template v-if="editingId">
                <tr class="bg-muted/60">
                    <td colspan="6" class="px-4 py-4">
                        <div class="mb-3 grid grid-cols-3 gap-3">
                            <label class="block"
                                ><span
                                    class="mb-1 block text-xs font-medium text-muted-foreground"
                                    >Emoji</span
                                ><input
                                    v-model="editForm.emoji"
                                    class="input-premium px-3 py-2 text-sm"
                            /></label>
                            <label class="col-span-2 block"
                                ><span
                                    class="mb-1 block text-xs font-medium text-muted-foreground"
                                    >Title</span
                                ><input
                                    v-model="editForm.title"
                                    class="input-premium px-3 py-2 text-sm"
                            /></label>
                            <label class="block"
                                ><span
                                    class="mb-1 block text-xs font-medium text-muted-foreground"
                                    >Difficulty</span
                                ><select
                                    v-model="editForm.difficulty"
                                    class="input-premium px-3 py-2 text-sm"
                                >
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select></label
                            >
                            <label class="block"
                                ><span
                                    class="mb-1 block text-xs font-medium text-muted-foreground"
                                    >Category</span
                                ><input
                                    v-model="editForm.category"
                                    class="input-premium px-3 py-2 text-sm"
                            /></label>
                            <label class="block"
                                ><span
                                    class="mb-1 block text-xs font-medium text-muted-foreground"
                                    >Weight</span
                                ><input
                                    v-model.number="editForm.weight"
                                    type="number"
                                    min="1"
                                    max="10"
                                    class="input-premium px-3 py-2 text-sm"
                            /></label>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                size="sm"
                                @click="saveEdit"
                                :disabled="editForm.processing"
                                >Save</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                @click="editingId = null"
                                >Cancel</Button
                            >
                        </div>
                    </td>
                </tr>
            </template>
            <tr
                v-for="challenge in challenges.data"
                :key="challenge.id"
                class="transition-colors hover:bg-muted/60"
            >
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span v-if="challenge.emoji" class="text-lg">{{
                            challenge.emoji
                        }}</span>
                        <p class="text-sm font-medium text-foreground">
                            {{ challenge.title }}
                        </p>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span
                        :class="[
                            'badge-soft capitalize',
                            challenge.difficulty === 'easy'
                                ? 'bg-success/10 text-success'
                                : challenge.difficulty === 'medium'
                                  ? 'bg-warning/10 text-warning'
                                  : 'bg-destructive/10 text-destructive',
                        ]"
                    >
                        {{ challenge.difficulty }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ challenge.category ?? '—' }}
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ challenge.weight }}
                </td>
                <td class="px-4 py-3">
                    <span
                        :class="[
                            'badge-soft',
                            challenge.active
                                ? 'bg-success/10 text-success'
                                : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        {{ challenge.active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <button
                            @click="startEdit(challenge)"
                            class="text-xs font-medium text-foreground hover:underline"
                        >
                            Edit
                        </button>
                        <button
                            @click="doArchive(challenge.id)"
                            class="text-xs text-warning hover:underline"
                        >
                            Archive
                        </button>
                        <button
                            @click="confirmDelete = challenge.id"
                            class="text-xs text-destructive hover:underline"
                        >
                            Delete
                        </button>
                    </div>
                </td>
            </tr>
        </AdminTable>

        <AdminPagination :links="challenges.links" />

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
                <div class="mb-2 flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-foreground">
                        Delete challenge?
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
                    This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="doDelete(confirmDelete!)"
                        class="flex-1"
                        >Delete</Button
                    >
                    <Button
                        variant="outline"
                        size="sm"
                        @click="confirmDelete = null"
                        class="flex-1"
                        >Cancel</Button
                    >
                </div>
            </div>
        </div>
    </div>
</template>
