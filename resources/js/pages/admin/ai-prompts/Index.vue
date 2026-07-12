<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  prompts: Record<string, Array<{
    id: number
    key: string
    label: string
    type: string
    content: string
    version: number
    active: boolean
    updated_at: string
  }>>
  filters: { type?: string }
}>()

const editingId = ref<number | null>(null)
const editContent = ref('')
const editLabel = ref('')

const editForm = useForm({
  label: '',
  content: '',
  description: '',
  active: true,
})

const startEdit = (prompt: { id: number; label: string; content: string; active: boolean }) => {
  editingId.value = prompt.id
  editForm.label = prompt.label
  editForm.content = prompt.content
  editForm.active = prompt.active
  editContent.value = prompt.content
  editLabel.value = prompt.label
}

const saveEdit = () => {
  editForm.put(route('admin.ai-prompts.update', editingId.value!), {
    onSuccess: () => {
      editingId.value = null
    },
  })
}

const doDelete = (id: number) => {
  if (confirm('Delete this prompt?')) {
    router.delete(route('admin.ai-prompts.destroy', id))
  }
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">AI Prompts</h1>
      <p class="text-gray-500 text-sm mt-1">Manage AI system and generation prompts.</p>
    </div>

    <div v-for="(groupPrompts, typeName) in prompts" :key="typeName" class="mb-8">
      <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3 capitalize">{{ String(typeName).replace('_', ' ') }}</h2>
      <div class="space-y-3">
        <div
          v-for="prompt in groupPrompts"
          :key="prompt.id"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
        >
          <div v-if="editingId !== prompt.id">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <p class="font-medium text-gray-900 text-sm">{{ prompt.label }}</p>
                <span class="text-xs text-gray-400 font-mono">v{{ prompt.version }}</span>
                <span
                  :class="[
                    'text-xs px-2 py-0.5 rounded-full',
                    prompt.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500',
                  ]"
                >
                  {{ prompt.active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div class="flex gap-2">
                <button @click="startEdit(prompt)" class="text-xs text-pink-500 hover:underline">Edit</button>
                <button @click="doDelete(prompt.id)" class="text-xs text-red-400 hover:underline">Delete</button>
              </div>
            </div>
            <p class="text-xs text-gray-400 font-mono mb-2">{{ prompt.key }}</p>
            <pre class="text-xs text-gray-600 bg-gray-50 rounded-xl p-3 overflow-auto max-h-32 whitespace-pre-wrap">{{ prompt.content }}</pre>
          </div>

          <div v-else class="space-y-3">
            <input v-model="editForm.label" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300" placeholder="Label" />
            <textarea
              v-model="editForm.content"
              rows="8"
              class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-pink-300"
            />
            <div class="flex items-center gap-2">
              <input id="active" v-model="editForm.active" type="checkbox" class="rounded" />
              <label for="active" class="text-xs text-gray-700">Active</label>
            </div>
            <div class="flex gap-2">
              <button @click="saveEdit" :disabled="editForm.processing" class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50">Save</button>
              <button @click="editingId = null" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <p v-if="!Object.keys(prompts).length" class="text-sm text-gray-400 text-center py-12 bg-white rounded-2xl border border-gray-100">
      No AI prompts configured yet.
    </p>
  </div>
</template>
