<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminPagination from '@/components/admin/AdminPagination.vue'
import AdminTable from '@/components/admin/AdminTable.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  couples: {
    data: Array<{
      id: number
      name: string
      email: string
      current_streak: number
      longest_streak: number
      questionnaires_completed: number
      moments_count: number
      partner: { id: number; name: string; email: string } | null
    }>
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
  filters: { search?: string }
}>()

const search = ref('')

const applyFilters = () => {
  router.get(route('admin.couples.index'), { search: search.value }, { preserveState: true })
}

const confirmDisconnect = ref<number | null>(null)

const doDisconnect = (id: number) => {
  router.post(route('admin.couples.disconnect', id), {}, {
    onSuccess: () => {
      confirmDisconnect.value = null
    },
  })
}

const columns = [
  { key: 'user1', label: 'User 1' },
  { key: 'user2', label: 'User 2' },
  { key: 'streak', label: 'Streak' },
  { key: 'completions', label: 'Completions' },
  { key: 'moments', label: 'Moments' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Couples</h1>
        <p class="text-gray-500 text-sm mt-1">{{ couples.total }} connected couples</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6 flex gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Search by name or email…"
        class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @keyup.enter="applyFilters"
      />
      <button
        @click="applyFilters"
        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
      >
        Search
      </button>
    </div>

    <AdminTable :columns="columns" empty="No couples found.">
      <tr
        v-for="couple in couples.data"
        :key="couple.id"
        class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
      >
        <td class="px-4 py-3">
          <div>
            <p class="font-medium text-gray-900 text-sm">{{ couple.name }}</p>
            <p class="text-xs text-gray-500">{{ couple.email }}</p>
          </div>
        </td>
        <td class="px-4 py-3">
          <div v-if="couple.partner">
            <p class="font-medium text-gray-900 text-sm">{{ couple.partner.name }}</p>
            <p class="text-xs text-gray-500">{{ couple.partner.email }}</p>
          </div>
          <span v-else class="text-xs text-gray-400">—</span>
        </td>
        <td class="px-4 py-3">
          <span class="text-sm font-medium text-pink-600">🔥 {{ couple.current_streak }}</span>
        </td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ couple.questionnaires_completed }}</td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ couple.moments_count }}</td>
        <td class="px-4 py-3">
          <button @click="confirmDisconnect = couple.id" class="text-xs text-red-500 hover:underline">Disconnect</button>
        </td>
      </tr>
    </AdminTable>

    <AdminPagination :links="couples.links" />

    <!-- Disconnect Modal -->
    <div v-if="confirmDisconnect" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-gray-900 mb-2">Disconnect Couple?</h3>
        <p class="text-sm text-gray-500 mb-6">This will remove the partner connection between these two users.</p>
        <div class="flex gap-3">
          <button @click="doDisconnect(confirmDisconnect!)" class="flex-1 bg-red-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition-colors">Disconnect</button>
          <button @click="confirmDisconnect = null" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>
