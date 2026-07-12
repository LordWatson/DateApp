<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminPagination from '@/components/admin/AdminPagination.vue'
import AdminTable from '@/components/admin/AdminTable.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  users: {
    data: Array<{
      id: number
      name: string
      email: string
      is_suspended: boolean
      onboarding_completed: boolean
      partner_id: number | null
      created_at: string
      role: { label: string } | null
      responses_count: number
    }>
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
  roles: Array<{ id: number; name: string; label: string }>
  filters: { search?: string; filter?: string }
}>()

const search = ref('')
const filter = ref('')

const applyFilters = () => {
  router.get(route('admin.users.index'), { search: search.value, filter: filter.value }, { preserveState: true })
}

const confirmDelete = ref<number | null>(null)
const suspendUserId = ref<number | null>(null)
const suspendReason = ref('')

const suspendForm = useForm({ reason: '' })

const doSuspend = (id: number) => {
  suspendForm.reason = suspendReason.value
  suspendForm.post(route('admin.users.suspend', id), {
    onSuccess: () => {
      suspendUserId.value = null
      suspendReason.value = ''
    },
  })
}

const doDelete = (id: number) => {
  router.delete(route('admin.users.destroy', id), {
    onSuccess: () => {
      confirmDelete.value = null
    },
  })
}

const columns = [
  { key: 'name', label: 'User' },
  { key: 'role', label: 'Role' },
  { key: 'status', label: 'Status' },
  { key: 'partner', label: 'Partner' },
  { key: 'responses', label: 'Responses' },
  { key: 'joined', label: 'Joined' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Users</h1>
        <p class="text-gray-500 text-sm mt-1">{{ users.total }} total users</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Search by name or email…"
        class="flex-1 min-w-48 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @keyup.enter="applyFilters"
      />
      <select
        v-model="filter"
        class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
        @change="applyFilters"
      >
        <option value="">All Users</option>
        <option value="active">Active</option>
        <option value="suspended">Suspended</option>
        <option value="connected">Connected</option>
        <option value="waiting">Waiting for Partner</option>
        <option value="admins">Admins</option>
      </select>
      <button
        @click="applyFilters"
        class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
      >
        Search
      </button>
    </div>

    <!-- Table -->
    <AdminTable :columns="columns" empty="No users found.">
      <tr
        v-for="user in users.data"
        :key="user.id"
        class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
      >
        <td class="px-4 py-3">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
              {{ user.name.charAt(0) }}
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ user.name }}</p>
              <p class="text-xs text-gray-500">{{ user.email }}</p>
            </div>
          </div>
        </td>
        <td class="px-4 py-3">
          <span v-if="user.role" class="text-xs px-2 py-1 rounded-full bg-purple-100 text-purple-700 font-medium">
            {{ user.role.label }}
          </span>
          <span v-else class="text-xs text-gray-400">User</span>
        </td>
        <td class="px-4 py-3">
          <span
            :class="[
              'text-xs px-2 py-1 rounded-full font-medium',
              user.is_suspended ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'
            ]"
          >
            {{ user.is_suspended ? 'Suspended' : 'Active' }}
          </span>
        </td>
        <td class="px-4 py-3 text-sm text-gray-600">
          {{ user.partner_id ? '✅ Connected' : '⏳ Waiting' }}
        </td>
        <td class="px-4 py-3 text-sm text-gray-600">{{ user.responses_count }}</td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ new Date(user.created_at).toLocaleDateString() }}</td>
        <td class="px-4 py-3">
          <div class="flex items-center gap-2">
            <Link :href="route('admin.users.show', user.id)" class="text-xs text-pink-500 hover:underline">View</Link>
            <button
              v-if="!user.is_suspended"
              @click="suspendUserId = user.id"
              class="text-xs text-yellow-600 hover:underline"
            >Suspend</button>
            <button
              v-else
              @click="router.post(route('admin.users.unsuspend', user.id))"
              class="text-xs text-green-600 hover:underline"
            >Unsuspend</button>
            <button
              @click="confirmDelete = user.id"
              class="text-xs text-red-500 hover:underline"
            >Delete</button>
          </div>
        </td>
      </tr>
    </AdminTable>

    <AdminPagination :links="users.links" />

    <!-- Suspend Modal -->
    <div v-if="suspendUserId" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-gray-900 mb-4">Suspend User</h3>
        <textarea
          v-model="suspendReason"
          placeholder="Reason for suspension (optional)…"
          class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 mb-4"
          rows="3"
        />
        <div class="flex gap-3">
          <button
            @click="doSuspend(suspendUserId!)"
            class="flex-1 bg-yellow-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-yellow-600 transition-colors"
          >Suspend</button>
          <button
            @click="suspendUserId = null"
            class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors"
          >Cancel</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div v-if="confirmDelete" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-gray-900 mb-2">Delete User?</h3>
        <p class="text-sm text-gray-500 mb-6">This action cannot be undone. All user data will be permanently deleted.</p>
        <div class="flex gap-3">
          <button
            @click="doDelete(confirmDelete!)"
            class="flex-1 bg-red-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition-colors"
          >Delete</button>
          <button
            @click="confirmDelete = null"
            class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors"
          >Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>
