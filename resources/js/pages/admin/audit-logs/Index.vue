<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminPagination from '@/components/admin/AdminPagination.vue'
import AdminTable from '@/components/admin/AdminTable.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  logs: {
    data: Array<{
      id: number
      action: string
      auditable_type: string | null
      auditable_id: number | null
      ip_address: string | null
      created_at: string
      user: { id: number; name: string; email: string } | null
    }>
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
  filters: { search?: string; action?: string }
}>()

const search = ref('')
const action = ref('')

const applyFilters = () => {
  router.get(route('admin.audit-logs.index'), { search: search.value, action: action.value }, { preserveState: true })
}

const columns = [
  { key: 'user', label: 'User' },
  { key: 'action', label: 'Action' },
  { key: 'target', label: 'Target' },
  { key: 'ip', label: 'IP Address' },
  { key: 'time', label: 'Time' },
]
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
      <p class="text-gray-500 text-sm mt-1">{{ logs.total }} total log entries</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Search by action or user…"
        class="flex-1 min-w-48 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @keyup.enter="applyFilters"
      />
      <input
        v-model="action"
        type="text"
        placeholder="Filter by action…"
        class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @keyup.enter="applyFilters"
      />
      <button
        @click="applyFilters"
        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
      >
        Search
      </button>
    </div>

    <AdminTable :columns="columns" empty="No audit logs found.">
      <tr
        v-for="log in logs.data"
        :key="log.id"
        class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
      >
        <td class="px-4 py-3">
          <div v-if="log.user">
            <p class="font-medium text-gray-900 text-sm">{{ log.user.name }}</p>
            <p class="text-xs text-gray-500">{{ log.user.email }}</p>
          </div>
          <span v-else class="text-xs text-gray-400">System</span>
        </td>
        <td class="px-4 py-3">
          <span class="text-xs font-mono px-2 py-1 rounded-lg bg-gray-100 text-gray-700">{{ log.action }}</span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-500">
          <span v-if="log.auditable_type">
            {{ log.auditable_type.split('\\').pop() }} #{{ log.auditable_id }}
          </span>
          <span v-else>—</span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ log.ip_address ?? '—' }}</td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ new Date(log.created_at).toLocaleString() }}</td>
      </tr>
    </AdminTable>

    <AdminPagination :links="logs.links" />
  </div>
</template>
