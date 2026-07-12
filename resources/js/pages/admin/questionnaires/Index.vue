<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminPagination from '@/components/admin/AdminPagination.vue'
import AdminTable from '@/components/admin/AdminTable.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  questionnaires: {
    data: Array<{
      id: number
      title: string
      emoji: string | null
      status: string
      visibility: string
      questions_count: number
      responses_count: number
      display_order: number
      is_seasonal: boolean
    }>
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
  filters: { search?: string; status?: string }
}>()

const search = ref('')
const status = ref('')

const applyFilters = () => {
  router.get(route('admin.questionnaires.index'), { search: search.value, status: status.value }, { preserveState: true })
}

const confirmDelete = ref<number | null>(null)

const doDelete = (id: number) => {
  router.delete(route('admin.questionnaires.destroy', id), {
    onSuccess: () => {
      confirmDelete.value = null
    },
  })
}

const doDuplicate = (id: number) => {
  router.post(route('admin.questionnaires.duplicate', id))
}

const columns = [
  { key: 'title', label: 'Questionnaire' },
  { key: 'status', label: 'Status' },
  { key: 'questions', label: 'Questions' },
  { key: 'responses', label: 'Responses' },
  { key: 'order', label: 'Order' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Questionnaires</h1>
        <p class="text-gray-500 text-sm mt-1">{{ questionnaires.total }} total</p>
      </div>
      <Link
        :href="route('admin.questionnaires.create')"
        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
      >
        + New Questionnaire
      </Link>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Search questionnaires…"
        class="flex-1 min-w-48 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @keyup.enter="applyFilters"
      />
      <select
        v-model="status"
        class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @change="applyFilters"
      >
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="draft">Draft</option>
        <option value="archived">Archived</option>
      </select>
      <button
        @click="applyFilters"
        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
      >
        Search
      </button>
    </div>

    <AdminTable :columns="columns" empty="No questionnaires found.">
      <tr
        v-for="q in questionnaires.data"
        :key="q.id"
        class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
      >
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <span v-if="q.emoji" class="text-xl">{{ q.emoji }}</span>
            <div>
              <p class="font-medium text-gray-900">{{ q.title }}</p>
              <span v-if="q.is_seasonal" class="text-xs text-purple-500">🌸 Seasonal</span>
            </div>
          </div>
        </td>
        <td class="px-4 py-3">
          <span
            :class="[
              'text-xs px-2 py-1 rounded-full font-medium capitalize',
              q.status === 'active' ? 'bg-green-100 text-green-700' :
              q.status === 'draft' ? 'bg-yellow-100 text-yellow-700' :
              'bg-gray-100 text-gray-600',
            ]"
          >
            {{ q.status }}
          </span>
        </td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ q.questions_count }}</td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ q.responses_count }}</td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ q.display_order }}</td>
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <Link :href="route('admin.questionnaires.edit', q.id)" class="text-xs text-pink-500 hover:underline">Edit</Link>
            <button @click="doDuplicate(q.id)" class="text-xs text-blue-500 hover:underline">Duplicate</button>
            <button @click="confirmDelete = q.id" class="text-xs text-red-500 hover:underline">Delete</button>
          </div>
        </td>
      </tr>
    </AdminTable>

    <AdminPagination :links="questionnaires.links" />

    <!-- Delete Modal -->
    <div v-if="confirmDelete" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-gray-900 mb-2">Delete Questionnaire?</h3>
        <p class="text-sm text-gray-500 mb-6">This will permanently delete the questionnaire and all its questions.</p>
        <div class="flex gap-3">
          <button
            @click="doDelete(confirmDelete!)"
            class="flex-1 bg-red-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition-colors"
          >
            Delete
          </button>
          <button
            @click="confirmDelete = null"
            class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
