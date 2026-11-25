<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Plus, X } from '@lucide/vue';
import { ref } from 'vue';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

defineProps<{
    achievements: {
        data: Array<{
            id: number;
            name: string;
            emoji: string;
            category: string;
            points: number;
            hidden: boolean;
            unlock_condition: string;
            unlock_value: number;
            users_count: number;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: { search?: string };
}>();

const showCreate = ref(false);
const editingId = ref<number | null>(null);
const confirmDelete = ref<number | null>(null);

const createForm = useForm({
    name: '',
    description: '',
    emoji: '🏆',
    artwork: '',
    category: 'general',
    points: 10,
    hidden: false,
    unlock_condition: 'questionnaires_completed',
    unlock_value: 1,
});

const editForm = useForm({
    name: '',
    description: '',
    emoji: '🏆',
    artwork: '',
    category: 'general',
    points: 10,
    hidden: false,
    unlock_condition: 'questionnaires_completed',
    unlock_value: 1,
});

const startEdit = (a: {
    id: number;
    name: string;
    emoji: string;
    category: string;
    points: number;
    hidden: boolean;
    unlock_condition: string;
    unlock_value: number;
}) => {
    editingId.value = a.id;
    editForm.name = a.name;
    editForm.emoji = a.emoji;
    editForm.category = a.category;
    editForm.points = a.points;
    editForm.hidden = a.hidden;
    editForm.unlock_condition = a.unlock_condition;
    editForm.unlock_value = a.unlock_value;
};

const saveEdit = () => {
    editForm.put(admin.achievements.update(editingId.value!).url, {
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const doCreate = () => {
    createForm.post(admin.achievements.store().url, {
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
};

const doDelete = (id: number) => {
    router.delete(admin.achievements.destroy(id).url, {
        onSuccess: () => {
            confirmDelete.value = null;
        },
    });
};

const columns = [
    { key: 'name', label: 'Achievement' },
    { key: 'category', label: 'Category' },
    { key: 'points', label: 'Points' },
    { key: 'condition', label: 'Unlock condition' },
    { key: 'unlocked', label: 'Unlocked by' },
    { key: 'actions', label: '' },
];
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Achievements
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ achievements.total }} total
                </p>
            </div>
            <Button size="sm" @click="showCreate = !showCreate">
                <Plus class="size-4" aria-hidden="true" />New achievement
            </Button>
        </div>

        <!-- Create Form -->
        <div
            v-if="showCreate"
            class="mb-6 space-y-3 card-premium rounded-2xl p-5"
        >
            <h3 class="text-lg font-semibold text-foreground">
                New achievement
            </h3>
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
                        >Name *</span
                    ><input
                        v-model="createForm.name"
                        required
                        class="input-premium px-3 py-2 text-sm"
                /></label>
                <label class="block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Category</span
                    ><input
                        v-model="createForm.category"
                        class="input-premium px-3 py-2 text-sm"
                /></label>
                <label class="block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Points</span
                    ><input
                        v-model.number="createForm.points"
                        type="number"
                        min="0"
                        class="input-premium px-3 py-2 text-sm"
                /></label>
                <label class="block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Unlock condition</span
                    ><input
                        v-model="createForm.unlock_condition"
                        class="input-premium px-3 py-2 text-sm"
                /></label>
                <label class="block"
                    ><span
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Unlock value</span
                    ><input
                        v-model.number="createForm.unlock_value"
                        type="number"
                        min="0"
                        class="input-premium px-3 py-2 text-sm"
                /></label>
            </div>
            <label class="block"
                ><span
                    class="mb-1 block text-xs font-medium text-muted-foreground"
                    >Description *</span
                ><textarea
                    v-model="createForm.description"
                    rows="2"
                    class="input-premium px-3 py-2 text-sm"
                />
            </label>
            <div class="flex items-center gap-2">
                <input
                    id="hidden"
                    v-model="createForm.hidden"
                    type="checkbox"
                    class="size-4 rounded accent-primary"
                />
                <label for="hidden" class="text-xs text-foreground"
                    >Hidden achievement</label
                >
            </div>
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

        <AdminTable :columns="columns" empty="No achievements found.">
            <tr
                v-for="achievement in achievements.data"
                :key="achievement.id"
                class="transition-colors hover:bg-muted/60"
            >
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ achievement.emoji }}</span>
                        <div>
                            <p class="text-sm font-medium text-foreground">
                                {{ achievement.name }}
                            </p>
                            <span
                                v-if="achievement.hidden"
                                class="text-xs text-muted-foreground"
                                >Hidden</span
                            >
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground capitalize">
                    {{ achievement.category }}
                </td>
                <td class="px-4 py-3">
                    <span class="badge-soft bg-warning/10 text-warning"
                        >{{ achievement.points }} pts</span
                    >
                </td>
                <td class="px-4 py-3 text-xs text-muted-foreground">
                    {{ achievement.unlock_condition }} ≥
                    {{ achievement.unlock_value }}
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ achievement.users_count }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <button
                            @click="startEdit(achievement)"
                            class="text-xs font-medium text-foreground hover:underline"
                        >
                            Edit
                        </button>
                        <button
                            @click="confirmDelete = achievement.id"
                            class="text-xs text-destructive hover:underline"
                        >
                            Delete
                        </button>
                    </div>
                </td>
            </tr>
        </AdminTable>

        <AdminPagination :links="achievements.links" />

        <!-- Edit Modal -->
        <div
            v-if="editingId"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-lg space-y-3 card-premium p-6"
                role="dialog"
                aria-modal="true"
            >
                <div class="mb-1 flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-foreground">
                        Edit achievement
                    </h2>
                    <button
                        type="button"
                        class="-mt-1 -mr-1 flex size-8 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Close"
                        @click="editingId = null"
                    >
                        <X class="size-4" aria-hidden="true" />
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"
                        ><span
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Emoji</span
                        ><input
                            v-model="editForm.emoji"
                            class="input-premium px-3 py-2 text-sm"
                    /></label>
                    <label class="block"
                        ><span
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Name</span
                        ><input
                            v-model="editForm.name"
                            class="input-premium px-3 py-2 text-sm"
                    /></label>
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
                            >Points</span
                        ><input
                            v-model.number="editForm.points"
                            type="number"
                            class="input-premium px-3 py-2 text-sm"
                    /></label>
                    <label class="block"
                        ><span
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Unlock condition</span
                        ><input
                            v-model="editForm.unlock_condition"
                            class="input-premium px-3 py-2 text-sm"
                    /></label>
                    <label class="block"
                        ><span
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Unlock value</span
                        ><input
                            v-model.number="editForm.unlock_value"
                            type="number"
                            class="input-premium px-3 py-2 text-sm"
                    /></label>
                </div>
                <div class="flex gap-3">
                    <Button
                        size="sm"
                        @click="saveEdit"
                        :disabled="editForm.processing"
                        class="flex-1"
                        >Save</Button
                    >
                    <Button
                        variant="outline"
                        size="sm"
                        @click="editingId = null"
                        class="flex-1"
                        >Cancel</Button
                    >
                </div>
            </div>
        </div>

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
                        Delete achievement?
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
