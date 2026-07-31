<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

type PromptTemplate = {
  id: number
  name: string
  system_prompt: string
  user_prompt_template: string
  description: string | null
  version: number
  active: boolean
  max_tokens: number | null
  updated_at: string
}

defineProps<{
  templates: PromptTemplate[]
}>()

const editingId = ref<number | null>(null)
const creating = ref(false)

const editForm = useForm({
  system_prompt: '',
  user_prompt_template: '',
  description: '' as string | null,
  active: true,
  max_tokens: null as number | null,
})

const createForm = useForm({
  name: '',
  system_prompt: '',
  user_prompt_template: '',
  description: '' as string | null,
  active: true,
  max_tokens: null as number | null,
})

const startEdit = (tpl: PromptTemplate) => {
  editingId.value = tpl.id
  editForm.system_prompt = tpl.system_prompt
  editForm.user_prompt_template = tpl.user_prompt_template
  editForm.description = tpl.description ?? ''
  editForm.active = tpl.active
  editForm.max_tokens = tpl.max_tokens
}

const cancelEdit = () => {
  editingId.value = null
  editForm.reset()
}

const saveEdit = () => {
  if (editingId.value === null) {
    return
  }
  editForm.put(route('admin.ai-prompts.update', editingId.value), {
    preserveScroll: true,
    onSuccess: () => {
      editingId.value = null
      editForm.reset()
    },
  })
}

const startCreate = () => {
  creating.value = true
  createForm.reset()
}

const cancelCreate = () => {
  creating.value = false
  createForm.reset()
}

const saveCreate = () => {
  createForm.post(route('admin.ai-prompts.store'), {
    preserveScroll: true,
    onSuccess: () => {
      creating.value = false
      createForm.reset()
    },
  })
}

const doDelete = (tpl: PromptTemplate) => {
  if (confirm(`Delete template "${tpl.name}"?`)) {
    router.delete(route('admin.ai-prompts.destroy', tpl.id), { preserveScroll: true })
  }
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">AI Prompt Templates</h1>
        <p class="text-gray-500 text-sm mt-1">Manage reusable AI prompt templates (system prompt + user prompt template).</p>
      </div>
      <button
        v-if="!creating"
        @click="startCreate"
        class="bg-pink-500 text-white px-4 py-2 rounded-2xl text-sm font-semibold hover:bg-pink-600 transition-colors"
      >
        + New template
      </button>
    </div>

    <div v-if="creating" class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 mb-6 space-y-3">
      <h2 class="text-sm font-semibold text-gray-700">New template</h2>
      <input
        v-model="createForm.name"
        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        placeholder="Name (e.g. date_night_plan)"
      />
      <p v-if="createForm.errors.name" class="text-xs text-red-500">{{ createForm.errors.name }}</p>

      <input
        v-model="createForm.description"
        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        placeholder="Description (optional)"
      />

      <label class="text-xs text-gray-500 uppercase tracking-wide font-semibold">System prompt</label>
      <textarea
        v-model="createForm.system_prompt"
        rows="6"
        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-pink-300"
      />
      <p v-if="createForm.errors.system_prompt" class="text-xs text-red-500">{{ createForm.errors.system_prompt }}</p>

      <label class="text-xs text-gray-500 uppercase tracking-wide font-semibold">User prompt template</label>
      <textarea
        v-model="createForm.user_prompt_template"
        rows="6"
        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-pink-300"
      />
      <p v-if="createForm.errors.user_prompt_template" class="text-xs text-red-500">{{ createForm.errors.user_prompt_template }}</p>

      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <input id="create-active" v-model="createForm.active" type="checkbox" class="rounded" />
          <label for="create-active" class="text-xs text-gray-700">Active</label>
        </div>
        <div class="flex items-center gap-2">
          <label for="create-max-tokens" class="text-xs text-gray-700">Max tokens</label>
          <input
            id="create-max-tokens"
            v-model.number="createForm.max_tokens"
            type="number"
            min="1"
            class="w-28 border border-gray-200 rounded-xl px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
            placeholder="default"
          />
        </div>
      </div>

      <div class="flex gap-2">
        <button
          @click="saveCreate"
          :disabled="createForm.processing"
          class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-pink-600 transition-colors disabled:opacity-50"
        >
          Create
        </button>
        <button
          @click="cancelCreate"
          class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-200 transition-colors"
        >
          Cancel
        </button>
      </div>
    </div>

    <div class="space-y-3">
      <div
        v-for="tpl in templates"
        :key="tpl.id"
        class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5"
      >
        <div v-if="editingId !== tpl.id">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="font-semibold text-gray-900 text-sm">{{ tpl.name }}</p>
              <span class="text-xs text-gray-400 font-mono">v{{ tpl.version }}</span>
              <span
                :class="[
                  'text-xs px-2 py-0.5 rounded-full',
                  tpl.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500',
                ]"
              >
                {{ tpl.active ? 'Active' : 'Inactive' }}
              </span>
              <span v-if="tpl.max_tokens" class="text-xs text-gray-400">max_tokens: {{ tpl.max_tokens }}</span>
            </div>
            <div class="flex gap-2">
              <button @click="startEdit(tpl)" class="text-xs text-gray-600 hover:underline">Edit</button>
              <button @click="doDelete(tpl)" class="text-xs text-red-400 hover:underline">Delete</button>
            </div>
          </div>
          <p v-if="tpl.description" class="text-xs text-gray-500 mb-2">{{ tpl.description }}</p>
          <div class="grid gap-2">
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold mb-1">System prompt</p>
              <pre class="text-xs text-gray-600 bg-gray-50 rounded-xl p-3 overflow-auto max-h-32 whitespace-pre-wrap">{{ tpl.system_prompt }}</pre>
            </div>
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold mb-1">User prompt template</p>
              <pre class="text-xs text-gray-600 bg-gray-50 rounded-xl p-3 overflow-auto max-h-32 whitespace-pre-wrap">{{ tpl.user_prompt_template }}</pre>
            </div>
          </div>
        </div>

        <div v-else class="space-y-3">
          <div class="flex items-center gap-2">
            <p class="font-semibold text-gray-900 text-sm">{{ tpl.name }}</p>
            <span class="text-xs text-gray-400 font-mono">v{{ tpl.version }} → v{{ tpl.version + 1 }}</span>
          </div>

          <input
            v-model="editForm.description"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
            placeholder="Description (optional)"
          />

          <label class="text-xs text-gray-500 uppercase tracking-wide font-semibold">System prompt</label>
          <textarea
            v-model="editForm.system_prompt"
            rows="8"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-pink-300"
          />
          <p v-if="editForm.errors.system_prompt" class="text-xs text-red-500">{{ editForm.errors.system_prompt }}</p>

          <label class="text-xs text-gray-500 uppercase tracking-wide font-semibold">User prompt template</label>
          <textarea
            v-model="editForm.user_prompt_template"
            rows="8"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-pink-300"
          />
          <p v-if="editForm.errors.user_prompt_template" class="text-xs text-red-500">{{ editForm.errors.user_prompt_template }}</p>

          <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
              <input :id="`active-${tpl.id}`" v-model="editForm.active" type="checkbox" class="rounded" />
              <label :for="`active-${tpl.id}`" class="text-xs text-gray-700">Active</label>
            </div>
            <div class="flex items-center gap-2">
              <label :for="`max-tokens-${tpl.id}`" class="text-xs text-gray-700">Max tokens</label>
              <input
                :id="`max-tokens-${tpl.id}`"
                v-model.number="editForm.max_tokens"
                type="number"
                min="1"
                class="w-28 border border-gray-200 rounded-xl px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
                placeholder="default"
              />
            </div>
          </div>

          <div class="flex gap-2">
            <button
              @click="saveEdit"
              :disabled="editForm.processing"
              class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-pink-600 transition-colors disabled:opacity-50"
            >
              Save
            </button>
            <button
              @click="cancelEdit"
              class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-200 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <p v-if="!templates.length && !creating" class="text-sm text-gray-400 text-center py-12 bg-white rounded-3xl border border-gray-100">
      No AI prompt templates configured yet.
    </p>
  </div>
</template>
