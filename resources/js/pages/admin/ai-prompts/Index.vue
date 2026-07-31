<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';

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

    editForm.put(route('admin.ai-prompts.update', editingId.value), {
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
    createForm.post(route('admin.ai-prompts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            createForm.reset();
        },
    });
};

const doDelete = (tpl: PromptTemplate) => {
    if (confirm(`Delete template "${tpl.name}"?`)) {
        router.delete(route('admin.ai-prompts.destroy', tpl.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    AI Prompt Templates
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Manage reusable AI prompt templates (system prompt + user
                    prompt template).
                </p>
            </div>
            <button
                v-if="!creating"
                @click="startCreate"
                class="rounded-2xl bg-pink-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-pink-600"
            >
                + New template
            </button>
        </div>

        <div
            v-if="creating"
            class="mb-6 space-y-3 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm"
        >
            <h2 class="text-sm font-semibold text-gray-700">New template</h2>
            <input
                v-model="createForm.name"
                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
                placeholder="Name (e.g. date_night_plan)"
            />
            <p v-if="createForm.errors.name" class="text-xs text-red-500">
                {{ createForm.errors.name }}
            </p>

            <input
                v-model="createForm.description"
                class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
                placeholder="Description (optional)"
            />

            <label
                class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >System prompt</label
            >
            <textarea
                v-model="createForm.system_prompt"
                rows="6"
                class="w-full rounded-xl border border-gray-200 px-3 py-2 font-mono text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
            />
            <p
                v-if="createForm.errors.system_prompt"
                class="text-xs text-red-500"
            >
                {{ createForm.errors.system_prompt }}
            </p>

            <label
                class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >User prompt template</label
            >
            <textarea
                v-model="createForm.user_prompt_template"
                rows="6"
                class="w-full rounded-xl border border-gray-200 px-3 py-2 font-mono text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
            />
            <p
                v-if="createForm.errors.user_prompt_template"
                class="text-xs text-red-500"
            >
                {{ createForm.errors.user_prompt_template }}
            </p>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <input
                        id="create-active"
                        v-model="createForm.active"
                        type="checkbox"
                        class="rounded"
                    />
                    <label for="create-active" class="text-xs text-gray-700"
                        >Active</label
                    >
                </div>
                <div class="flex items-center gap-2">
                    <label for="create-max-tokens" class="text-xs text-gray-700"
                        >Max tokens</label
                    >
                    <input
                        id="create-max-tokens"
                        v-model.number="createForm.max_tokens"
                        type="number"
                        min="1"
                        class="w-28 rounded-xl border border-gray-200 px-2 py-1 text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
                        placeholder="default"
                    />
                </div>
            </div>

            <div class="flex gap-2">
                <button
                    @click="saveCreate"
                    :disabled="createForm.processing"
                    class="rounded-xl bg-pink-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-pink-600 disabled:opacity-50"
                >
                    Create
                </button>
                <button
                    @click="cancelCreate"
                    class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200"
                >
                    Cancel
                </button>
            </div>
        </div>

        <div class="space-y-3">
            <div
                v-for="tpl in templates"
                :key="tpl.id"
                class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div v-if="editingId !== tpl.id">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ tpl.name }}
                            </p>
                            <span class="font-mono text-xs text-gray-400"
                                >v{{ tpl.version }}</span
                            >
                            <span
                                :class="[
                                    'rounded-full px-2 py-0.5 text-xs',
                                    tpl.active
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-gray-100 text-gray-500',
                                ]"
                            >
                                {{ tpl.active ? 'Active' : 'Inactive' }}
                            </span>
                            <span
                                v-if="tpl.max_tokens"
                                class="text-xs text-gray-400"
                                >max_tokens: {{ tpl.max_tokens }}</span
                            >
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="startEdit(tpl)"
                                class="text-xs text-gray-600 hover:underline"
                            >
                                Edit
                            </button>
                            <button
                                @click="doDelete(tpl)"
                                class="text-xs text-red-400 hover:underline"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                    <p
                        v-if="tpl.description"
                        class="mb-2 text-xs text-gray-500"
                    >
                        {{ tpl.description }}
                    </p>
                    <div class="grid gap-2">
                        <div>
                            <p
                                class="mb-1 text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                            >
                                System prompt
                            </p>
                            <pre
                                class="max-h-32 overflow-auto rounded-xl bg-gray-50 p-3 text-xs whitespace-pre-wrap text-gray-600"
                                >{{ tpl.system_prompt }}</pre>
                        </div>
                        <div>
                            <p
                                class="mb-1 text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                            >
                                User prompt template
                            </p>
                            <pre
                                class="max-h-32 overflow-auto rounded-xl bg-gray-50 p-3 text-xs whitespace-pre-wrap text-gray-600"
                                >{{ tpl.user_prompt_template }}</pre>
                        </div>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900">
                            {{ tpl.name }}
                        </p>
                        <span class="font-mono text-xs text-gray-400"
                            >v{{ tpl.version }} → v{{ tpl.version + 1 }}</span
                        >
                    </div>

                    <input
                        v-model="editForm.description"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
                        placeholder="Description (optional)"
                    />

                    <label
                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >System prompt</label
                    >
                    <textarea
                        v-model="editForm.system_prompt"
                        rows="8"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 font-mono text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
                    />
                    <p
                        v-if="editForm.errors.system_prompt"
                        class="text-xs text-red-500"
                    >
                        {{ editForm.errors.system_prompt }}
                    </p>

                    <label
                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >User prompt template</label
                    >
                    <textarea
                        v-model="editForm.user_prompt_template"
                        rows="8"
                        class="w-full rounded-xl border border-gray-200 px-3 py-2 font-mono text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
                    />
                    <p
                        v-if="editForm.errors.user_prompt_template"
                        class="text-xs text-red-500"
                    >
                        {{ editForm.errors.user_prompt_template }}
                    </p>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <input
                                :id="`active-${tpl.id}`"
                                v-model="editForm.active"
                                type="checkbox"
                                class="rounded"
                            />
                            <label
                                :for="`active-${tpl.id}`"
                                class="text-xs text-gray-700"
                                >Active</label
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <label
                                :for="`max-tokens-${tpl.id}`"
                                class="text-xs text-gray-700"
                                >Max tokens</label
                            >
                            <input
                                :id="`max-tokens-${tpl.id}`"
                                v-model.number="editForm.max_tokens"
                                type="number"
                                min="1"
                                class="w-28 rounded-xl border border-gray-200 px-2 py-1 text-sm text-gray-600 focus:ring-2 focus:ring-pink-300 focus:outline-none"
                                placeholder="default"
                            />
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="saveEdit"
                            :disabled="editForm.processing"
                            class="rounded-xl bg-pink-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-pink-600 disabled:opacity-50"
                        >
                            Save
                        </button>
                        <button
                            @click="cancelEdit"
                            class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p
            v-if="!templates.length && !creating"
            class="rounded-3xl border border-gray-100 bg-white py-12 text-center text-sm text-gray-400"
        >
            No AI prompt templates configured yet.
        </p>
    </div>
</template>
