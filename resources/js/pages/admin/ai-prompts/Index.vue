<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

type PromptTemplate = {
    id: number;
    name: string;
    system_prompt: string;
    user_prompt_template: string;
    description: string | null;
    version: number;
    active: boolean;
    max_tokens: number | null;
    updated_at: string;
};

defineProps<{
    templates: PromptTemplate[];
}>();

const editingId = ref<number | null>(null);
const creating = ref(false);

const editForm = useForm({
    system_prompt: '',
    user_prompt_template: '',
    description: '' as string | null,
    active: true,
    max_tokens: null as number | null,
});

const createForm = useForm({
    name: '',
    system_prompt: '',
    user_prompt_template: '',
    description: '' as string | null,
    active: true,
    max_tokens: null as number | null,
});

const startEdit = (tpl: PromptTemplate) => {
    editingId.value = tpl.id;
    editForm.system_prompt = tpl.system_prompt;
    editForm.user_prompt_template = tpl.user_prompt_template;
    editForm.description = tpl.description ?? '';
    editForm.active = tpl.active;
    editForm.max_tokens = tpl.max_tokens;
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const saveEdit = () => {
    if (editingId.value === null) {
        return;
    }

    editForm.put(admin.aiPrompts.update(editingId.value).url, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });
};

const startCreate = () => {
    creating.value = true;
    createForm.reset();
};

const cancelCreate = () => {
    creating.value = false;
    createForm.reset();
};

const saveCreate = () => {
    createForm.post(admin.aiPrompts.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            createForm.reset();
        },
    });
};

const doDelete = (tpl: PromptTemplate) => {
    if (confirm(`Delete template "${tpl.name}"?`)) {
        router.delete(admin.aiPrompts.destroy(tpl.id).url, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    AI Prompt Templates
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage reusable AI prompt templates (system prompt + user
                    prompt template).
                </p>
            </div>
            <Button size="sm" v-if="!creating" @click="startCreate">
                <Plus class="size-4" aria-hidden="true" />New template
            </Button>
        </div>

        <div
            v-if="creating"
            class="mb-6 space-y-3 card-premium rounded-2xl p-5"
        >
            <h2 class="text-lg font-semibold text-foreground">New template</h2>
            <label class="block"
                ><span
                    class="mb-1 block text-xs font-medium text-muted-foreground"
                    >Name</span
                ><input
                    v-model="createForm.name"
                    class="input-premium px-3 py-2 text-sm"
                    placeholder="Name (e.g. date_night_plan)"
            /></label>
            <p v-if="createForm.errors.name" class="text-xs text-destructive">
                {{ createForm.errors.name }}
            </p>

            <label class="block"
                ><span
                    class="mb-1 block text-xs font-medium text-muted-foreground"
                    >Description (optional)</span
                ><input
                    v-model="createForm.description"
                    class="input-premium px-3 py-2 text-sm"
            /></label>

            <label class="text-xs font-medium text-muted-foreground"
                >System prompt</label
            >
            <textarea
                v-model="createForm.system_prompt"
                rows="6"
                class="input-premium px-3 py-2 font-mono text-sm"
            />
            <p
                v-if="createForm.errors.system_prompt"
                class="text-xs text-destructive"
            >
                {{ createForm.errors.system_prompt }}
            </p>

            <label class="text-xs font-medium text-muted-foreground"
                >User prompt template</label
            >
            <textarea
                v-model="createForm.user_prompt_template"
                rows="6"
                class="input-premium px-3 py-2 font-mono text-sm"
            />
            <p
                v-if="createForm.errors.user_prompt_template"
                class="text-xs text-destructive"
            >
                {{ createForm.errors.user_prompt_template }}
            </p>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <input
                        id="create-active"
                        v-model="createForm.active"
                        type="checkbox"
                        class="size-4 rounded accent-primary"
                    />
                    <label for="create-active" class="text-xs text-foreground"
                        >Active</label
                    >
                </div>
                <div class="flex items-center gap-2">
                    <label
                        for="create-max-tokens"
                        class="text-xs text-foreground"
                        >Max tokens</label
                    >
                    <label class="block"
                        ><span
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Max tokens</span
                        ><input
                            id="create-max-tokens"
                            v-model.number="createForm.max_tokens"
                            type="number"
                            min="1"
                            class="input-premium w-28 px-2 py-1.5 text-sm"
                            placeholder="default"
                    /></label>
                </div>
            </div>

            <div class="flex gap-2">
                <Button
                    size="sm"
                    @click="saveCreate"
                    :disabled="createForm.processing"
                >
                    Create
                </Button>
                <Button variant="outline" size="sm" @click="cancelCreate">
                    Cancel
                </Button>
            </div>
        </div>

        <div class="space-y-3">
            <div
                v-for="tpl in templates"
                :key="tpl.id"
                class="card-premium rounded-2xl p-5"
            >
                <div v-if="editingId !== tpl.id">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-foreground">
                                {{ tpl.name }}
                            </p>
                            <span
                                class="font-mono text-xs text-muted-foreground"
                                >v{{ tpl.version }}</span
                            >
                            <span
                                :class="[
                                    'badge-soft',
                                    tpl.active
                                        ? 'bg-success/10 text-success'
                                        : 'bg-muted text-muted-foreground',
                                ]"
                            >
                                {{ tpl.active ? 'Active' : 'Inactive' }}
                            </span>
                            <span
                                v-if="tpl.max_tokens"
                                class="text-xs text-muted-foreground"
                                >max_tokens: {{ tpl.max_tokens }}</span
                            >
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="startEdit(tpl)"
                                class="text-xs font-medium text-foreground hover:underline"
                            >
                                Edit
                            </button>
                            <button
                                @click="doDelete(tpl)"
                                class="text-xs text-destructive hover:underline"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                    <p
                        v-if="tpl.description"
                        class="mb-2 text-xs text-muted-foreground"
                    >
                        {{ tpl.description }}
                    </p>
                    <div class="grid gap-2">
                        <div>
                            <p
                                class="mb-1 text-xs font-medium text-muted-foreground"
                            >
                                System prompt
                            </p>
                            <pre
                                class="max-h-32 overflow-auto rounded-xl bg-muted p-3 text-xs whitespace-pre-wrap text-muted-foreground"
                                >{{ tpl.system_prompt }}</pre>
                        </div>
                        <div>
                            <p
                                class="mb-1 text-xs font-medium text-muted-foreground"
                            >
                                User prompt template
                            </p>
                            <pre
                                class="max-h-32 overflow-auto rounded-xl bg-muted p-3 text-xs whitespace-pre-wrap text-muted-foreground"
                                >{{ tpl.user_prompt_template }}</pre>
                        </div>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-foreground">
                            {{ tpl.name }}
                        </p>
                        <span class="font-mono text-xs text-muted-foreground"
                            >v{{ tpl.version }} → v{{ tpl.version + 1 }}</span
                        >
                    </div>

                    <label class="block"
                        ><span
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Description (optional)</span
                        ><input
                            v-model="editForm.description"
                            class="input-premium px-3 py-2 text-sm"
                    /></label>

                    <label class="text-xs font-medium text-muted-foreground"
                        >System prompt</label
                    >
                    <textarea
                        v-model="editForm.system_prompt"
                        rows="8"
                        class="input-premium px-3 py-2 font-mono text-sm"
                    />
                    <p
                        v-if="editForm.errors.system_prompt"
                        class="text-xs text-destructive"
                    >
                        {{ editForm.errors.system_prompt }}
                    </p>

                    <label class="text-xs font-medium text-muted-foreground"
                        >User prompt template</label
                    >
                    <textarea
                        v-model="editForm.user_prompt_template"
                        rows="8"
                        class="input-premium px-3 py-2 font-mono text-sm"
                    />
                    <p
                        v-if="editForm.errors.user_prompt_template"
                        class="text-xs text-destructive"
                    >
                        {{ editForm.errors.user_prompt_template }}
                    </p>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <input
                                :id="`active-${tpl.id}`"
                                v-model="editForm.active"
                                type="checkbox"
                                class="size-4 rounded accent-primary"
                            />
                            <label
                                :for="`active-${tpl.id}`"
                                class="text-xs text-foreground"
                                >Active</label
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <label
                                :for="`max-tokens-${tpl.id}`"
                                class="text-xs text-foreground"
                                >Max tokens</label
                            >
                            <label class="block"
                                ><span
                                    class="mb-1 block text-xs font-medium text-muted-foreground"
                                    >Max tokens</span
                                ><input
                                    :id="`max-tokens-${tpl.id}`"
                                    v-model.number="editForm.max_tokens"
                                    type="number"
                                    min="1"
                                    class="input-premium w-28 px-2 py-1.5 text-sm"
                                    placeholder="default"
                            /></label>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            @click="saveEdit"
                            :disabled="editForm.processing"
                        >
                            Save
                        </Button>
                        <Button variant="outline" size="sm" @click="cancelEdit">
                            Cancel
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <p
            v-if="!templates.length && !creating"
            class="card-premium rounded-2xl py-12 text-center text-sm text-muted-foreground"
        >
            No AI prompt templates configured yet.
        </p>
    </div>
</template>
