<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminPagination from '@/components/admin/AdminPagination.vue'
import AdminTable from '@/components/admin/AdminTable.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  achievements: {
    data: Array<{
      id: number
      name: string
      emoji: string
      category: string
      points: number
      hidden: boolean
      unlock_condition: string
      unlock_value: number
      users_count: number
    }>
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
  filters: { search?: string }
}>()

const showCreate = ref(false)
const editingId = ref<number | null>(null)
const confirmDelete = ref<number | null>(null)

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
})

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
})

const startEdit = (a: { id: number; name: string; emoji: string; category: string; points: number; hidden: boolean; unlock_condition: string; unlock_value: number }) => {
  editingId.value = a.id
  editForm.name = a.name
  editForm.emoji = a.emoji
  editForm.category = a.category
  editForm.points = a.points
  editForm.hidden = a.hidden
  editForm.unlock_condition = a.unlock_condition
  editForm.unlock_value = a.unlock_value
}

const saveEdit = () => {
  editForm.put(route('admin.achievements.update', editingId.value!), {
    onSuccess: () => {
      editingId.value = null
    },
  })
}

const doCreate = () => {
  createForm.post(route('admin.achievements.store'), {
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })
}

const doDelete = (id: number) => {
  router.delete(route('admin.achievements.destroy', id), {
    onSuccess: () => {
      confirmDelete.value = null
    },
  })
}

const columns = [
  { key: 'name', label: 'Achievement' },
  { key: 'category', label: 'Category' },
  { key: 'points', label: 'Points' },
  { key: 'condition', label: 'Unlock Condition' },
  { key: 'unlocked', label: 'Unlocked By' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Achievements</h1>
        <p class="text-gray-500 text-sm mt-1">{{ achievements.total }} total</p>
      </div>
      <button
        @click="showCreate = !showCreate"
        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
      >
        + New Achievement
      </button>
    </div>

    <!-- Create Form -->
    <div v-if="showCreate" class="bg-pink-50 rounded-2xl border border-pink-100 p-5 mb-6 space-y-3">
      <h3 class="font-semibold text-gray-900">New Achievement</h3>
      <div class="grid grid-cols-2 gap-3">
        <input v-model="createForm.emoji" placeholder="Emoji" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
        <input v-model="createForm.name" placeholder="Name *" required class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
        <input v-model="createForm.category" placeholder="Category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
        <input v-model.number="createForm.points" type="number" min="0" placeholder="Points" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
        <input v-model="createForm.unlock_condition" placeholder="Unlock condition" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
        <input v-model.number="createForm.unlock_value" type="number" min="0" placeholder="Unlock value" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
      </div>
      <textarea v-model="createForm.description" placeholder="Description *" rows="2" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
      <div class="flex items-center gap-2">
        <input id="hidden" v-model="createForm.hidden" type="checkbox" class="rounded" />
        <label for="hidden" class="text-xs text-gray-700">Hidden achievement</label>
      </div>
      <div class="flex gap-2">
        <button @click="doCreate" :disabled="createForm.processing" class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50">Create</button>
        <button @click="showCreate = false" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
      </div>
    </div>

    <AdminTable :columns="columns" empty="No achievements found.">
      <tr
        v-for="achievement in achievements.data"
        :key="achievement.id"
        class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
      >
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">{{ achievement.emoji }}</span>
            <div>
              <p class="font-medium text-gray-900 text-sm">{{ achievement.name }}</p>
              <span v-if="achievement.hidden" class="text-xs text-gray-400">Hidden</span>
            </div>
          </div>
        </td>
        <td class="px-4 py-3 text-sm text-gray-600 capitalize">{{ achievement.category }}</td>
        <td class="px-4 py-3">
          <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium">{{ achievement.points }} pts</span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ achievement.unlock_condition }} ≥ {{ achievement.unlock_value }}</td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ achievement.users_count }}</td>
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <button @click="startEdit(achievement)" class="text-xs text-pink-500 hover:underline">Edit</button>
            <button @click="confirmDelete = achievement.id" class="text-xs text-red-500 hover:underline">Delete</button>
          </div>
        </td>
      </tr>
    </AdminTable>

    <AdminPagination :links="achievements.links" />

    <!-- Edit Modal -->
    <div v-if="editingId" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-xl space-y-3">
        <h3 class="font-bold text-gray-900">Edit Achievement</h3>
        <div class="grid grid-cols-2 gap-3">
          <input v-model="editForm.emoji" placeholder="Emoji" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300" />
          <input v-model="editForm.name" placeholder="Name" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300" />
          <input v-model="editForm.category" placeholder="Category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300" />
          <input v-model.number="editForm.points" type="number" placeholder="Points" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300" />
          <input v-model="editForm.unlock_condition" placeholder="Unlock condition" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300" />
          <input v-model.number="editForm.unlock_value" type="number" placeholder="Unlock value" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300" />
        </div>
        <div class="flex gap-3">
          <button @click="saveEdit" :disabled="editForm.processing" class="flex-1 bg-pink-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50">Save</button>
          <button @click="editingId = null" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="confirmDelete" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-gray-900 mb-2">Delete Achievement?</h3>
        <p class="text-sm text-gray-500 mb-6">This action cannot be undone.</p>
        <div class="flex gap-3">
          <button @click="doDelete(confirmDelete!)" class="flex-1 bg-red-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition-colors">Delete</button>
          <button @click="confirmDelete = null" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>
