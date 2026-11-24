<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { X, Flame } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps<{
    couples: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            current_streak: number;
            longest_streak: number;
            questionnaires_completed: number;
            moments_count: number;
            partner: { id: number; name: string; email: string } | null;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: { search?: string };
}>();

const search = ref('');

const applyFilters = () => {
    router.get(
        route('admin.couples.index'),
        { search: search.value },
        { preserveState: true },
    );
};

const confirmDisconnect = ref<number | null>(null);

const doDisconnect = (id: number) => {
    router.post(
        route('admin.couples.disconnect', id),
        {},
        {
            onSuccess: () => {
                confirmDisconnect.value = null;
            },
        },
    );
};

const columns = [
    { key: 'user1', label: 'User 1' },
    { key: 'user2', label: 'User 2' },
    { key: 'streak', label: 'Streak' },
    { key: 'completions', label: 'Completions' },
    { key: 'moments', label: 'Moments' },
    { key: 'actions', label: '' },
];
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">Couples</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ couples.total }} connected couples
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex gap-3 card-premium rounded-2xl p-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search by name or email…"
                class="input-premium flex-1 px-3 py-2 text-sm"
                @keyup.enter="applyFilters"
                aria-label="Search by name or email"
            />
            <Button size="sm" @click="applyFilters"> Search </Button>
        </div>

        <AdminTable :columns="columns" empty="No couples found.">
            <tr
                v-for="couple in couples.data"
                :key="couple.id"
                class="transition-colors hover:bg-muted/60"
            >
                <td class="px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-foreground">
                            {{ couple.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ couple.email }}
                        </p>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div v-if="couple.partner">
                        <p class="text-sm font-medium text-foreground">
                            {{ couple.partner.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ couple.partner.email }}
                        </p>
                    </div>
                    <span v-else class="text-xs text-muted-foreground">—</span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm font-medium text-primary"
                        ><Flame class="size-4" aria-hidden="true" />
                        {{ couple.current_streak }}</span
                    >
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ couple.questionnaires_completed }}
                </td>
                <td class="px-4 py-3 text-sm text-muted-foreground">
                    {{ couple.moments_count }}
                </td>
                <td class="px-4 py-3">
                    <button
                        @click="confirmDisconnect = couple.id"
                        class="text-xs text-destructive hover:underline"
                    >
                        Disconnect
                    </button>
                </td>
            </tr>
        </AdminTable>

        <AdminPagination :links="couples.links" />

        <!-- Disconnect Modal -->
        <div
            v-if="confirmDisconnect"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md card-premium p-6"
                role="dialog"
                aria-modal="true"
            >
                <div class="mb-2 flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-foreground">
                        Disconnect couple?
                    </h2>
                    <button
                        type="button"
                        class="-mt-1 -mr-1 flex size-8 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Close"
                        @click="confirmDisconnect = null"
                    >
                        <X class="size-4" aria-hidden="true" />
                    </button>
                </div>
                <p class="mb-6 text-sm text-muted-foreground">
                    This will remove the partner connection between these two
                    users.
                </p>
                <div class="flex gap-3">
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="doDisconnect(confirmDisconnect!)"
                        class="flex-1"
                        >Disconnect</Button
                    >
                    <Button
                        variant="outline"
                        size="sm"
                        @click="confirmDisconnect = null"
                        class="flex-1"
                        >Cancel</Button
                    >
                </div>
            </div>
        </div>
    </div>
</template>
