<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminPagination from '@/components/admin/AdminPagination.vue'
import AdminTable from '@/components/admin/AdminTable.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  challenges: {
    data: Array<{
      id: number
      title: string
      emoji: string | null
      difficulty: string
      category: string | null
      season: string | null
      active: boolean
      archived: boolean
      weight: number
    }>
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
  filters: { search?: string; difficulty?: string; category?: string }
}>()

const search = ref('')
const difficulty = ref('')

const applyFilters = () => {
  router.get(route('admin.challenges.index'), { search: search.value, difficulty: difficulty.value }, { preserveState: true })
}

const showCreate = ref(false)
const editingId = ref<number | null>(null)
const confirmDelete = ref<number | null>(null)

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
})

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
})

const startEdit = (challenge: { id: number; title: string; emoji: string | null; difficulty: string; category: string | null; season: string | null; weight: number; active: boolean }) => {
  editingId.value = challenge.id
  editForm.title = challenge.title
  editForm.emoji = challenge.emoji ?? ''
  editForm.difficulty = challenge.difficulty
  editForm.category = challenge.category ?? ''
  editForm.season = challenge.season ?? ''
  editForm.weight = challenge.weight
  editForm.active = challenge.active
}

const saveEdit = () => {
  editForm.put(route('admin.challenges.update', editingId.value!), {
    onSuccess: () => {
      editingId.value = null
    },
  })
}

const doCreate = () => {
  createForm.post(route('admin.challenges.store'), {
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })
}

const doDelete = (id: number) => {
  router.delete(route('admin.challenges.destroy', id), {
    onSuccess: () => {
      confirmDelete.value = null
    },
  })
}

const doArchive = (id: number) => {
  router.post(route('admin.challenges.archive', id))
}

const columns = [
  { key: 'title', label: 'Challenge' },
  { key: 'difficulty', label: 'Difficulty' },
  { key: 'category', label: 'Category' },
  { key: 'weight', label: 'Weight' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Challenges</h1>
        <p class="text-gray-500 text-sm mt-1">{{ challenges.total }} total</p>
      </div>
      <button
        @click="showCreate = !showCreate"
        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
      >
        + New Challenge
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Search challenges…"
        class="flex-1 min-w-48 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @keyup.enter="applyFilters"
      />
      <select
        v-model="difficulty"
        class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @change="applyFilters"
      >
        <option value="">All Difficulties</option>
        <option value="easy">Easy</option>
        <option value="medium">Medium</option>
        <option value="hard">Hard</option>
      </select>
      <button
        @click="applyFilters"
        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
      >
        Search
      </button>
    </div>

    <!-- Create Form -->
    <div v-if="showCreate" class="bg-pink-50 rounded-2xl border border-pink-100 p-5 mb-6 space-y-3">
      <h3 class="font-semibold text-gray-900">New Challenge</h3>
      <div class="grid grid-cols-2 gap-3">
        <input v-model="createForm.emoji" placeholder="Emoji" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
        <input v-model="createForm.title" placeholder="Title *" required class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
        <select v-model="createForm.difficulty" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white">
          <option value="easy">Easy</option>
          <option value="medium">Medium</option>
          <option value="hard">Hard</option>
        </select>
        <input v-model="createForm.category" placeholder="Category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
      </div>
      <textarea v-model="createForm.description" placeholder="Description" rows="2" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
      <div class="flex gap-2">
        <button @click="doCreate" :disabled="createForm.processing" class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50">Create</button>
        <button @click="showCreate = false" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
      </div>
    </div>

    <AdminTable :columns="columns" empty="No challenges found.">
      <template v-if="editingId">
        <tr class="border-b border-pink-100 bg-pink-50">
          <td colspan="6" class="px-4 py-4">
            <div class="grid grid-cols-3 gap-3 mb-3">
              <input v-model="editForm.emoji" placeholder="Emoji" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
              <input v-model="editForm.title" placeholder="Title" class="col-span-2 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
              <select v-model="editForm.difficulty" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white">
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
              </select>
              <input v-model="editForm.category" placeholder="Category" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
              <input v-model.number="editForm.weight" type="number" min="1" max="10" placeholder="Weight" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
            </div>
            <div class="flex gap-2">
              <button @click="saveEdit" :disabled="editForm.processing" class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50">Save</button>
              <button @click="editingId = null" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
            </div>
          </td>
        </tr>
      </template>
      <tr
        v-for="challenge in challenges.data"
        :key="challenge.id"
        class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
      >
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <span v-if="challenge.emoji" class="text-lg">{{ challenge.emoji }}</span>
            <p class="font-medium text-gray-900 text-sm">{{ challenge.title }}</p>
          </div>
        </td>
        <td class="px-4 py-3">
          <span
            :class="[
              'text-xs px-2 py-1 rounded-full font-medium capitalize',
              challenge.difficulty === 'easy' ? 'bg-green-100 text-green-700' :
              challenge.difficulty === 'medium' ? 'bg-yellow-100 text-yellow-700' :
              'bg-red-100 text-red-700',
            ]"
          >
            {{ challenge.difficulty }}
          </span>
        </td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ challenge.category ?? '—' }}</td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ challenge.weight }}</td>
        <td class="px-4 py-3">
          <span :class="['text-xs px-2 py-1 rounded-full font-medium', challenge.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
            {{ challenge.active ? 'Active' : 'Inactive' }}
          </span>
        </td>
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <button @click="startEdit(challenge)" class="text-xs text-pink-500 hover:underline">Edit</button>
            <button @click="doArchive(challenge.id)" class="text-xs text-yellow-600 hover:underline">Archive</button>
            <button @click="confirmDelete = challenge.id" class="text-xs text-red-500 hover:underline">Delete</button>
          </div>
        </td>
      </tr>
    </AdminTable>

    <AdminPagination :links="challenges.links" />

    <!-- Delete Modal -->
    <div v-if="confirmDelete" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-gray-900 mb-2">Delete Challenge?</h3>
        <p class="text-sm text-gray-500 mb-6">This action cannot be undone.</p>
        <div class="flex gap-3">
          <button @click="doDelete(confirmDelete!)" class="flex-1 bg-red-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition-colors">Delete</button>
          <button @click="confirmDelete = null" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>
