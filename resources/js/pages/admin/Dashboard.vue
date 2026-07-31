<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  stats: {
    total_users: number
    couples: number
    active_questionnaires: number
    completions_today: number
    plans_today: number
    love_notes_today: number
    failed_jobs: number
    queued_jobs: number
  }
  newest_users: Array<{ id: number; name: string; email: string; created_at: string }>
  daily_users: Array<{ date: string; count: number }>
  daily_completions: Array<{ date: string; count: number }>
}>()

const statCards = computed(() => [
  { label: 'Total Users', value: props.stats.total_users, icon: '👥', color: 'bg-blue-50 text-blue-600', href: '/admin/users' },
  { label: 'Couples', value: Math.floor(props.stats.couples), icon: '💑', color: 'bg-pink-50 text-pink-600', href: '/admin/couples' },
  { label: 'Active Questionnaires', value: props.stats.active_questionnaires, icon: '📝', color: 'bg-purple-50 text-purple-600', href: '/admin/questionnaires' },
  { label: 'Completions Today', value: props.stats.completions_today, icon: '✅', color: 'bg-green-50 text-green-600', href: '/admin/analytics' },
  { label: 'Plans Today', value: props.stats.plans_today, icon: '🌙', color: 'bg-indigo-50 text-indigo-600', href: '/admin/analytics' },
  { label: 'Love Notes Today', value: props.stats.love_notes_today, icon: '💌', color: 'bg-rose-50 text-rose-600', href: '/admin/analytics' },
  { label: 'Queued Jobs', value: props.stats.queued_jobs, icon: '⏳', color: 'bg-yellow-50 text-yellow-600', href: '/admin' },
  { label: 'Failed Jobs', value: props.stats.failed_jobs, icon: '❌', color: props.stats.failed_jobs > 0 ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-600', href: '/admin' },
])
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
      <p class="text-gray-500 text-sm mt-1">Welcome back. Here's what's happening today.</p>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <Link
        v-for="card in statCards"
        :key="card.label"
        :href="card.href"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5"
      >
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-xl mb-3', card.color]">
          {{ card.icon }}
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ card.value.toLocaleString() }}</p>
        <p class="text-sm text-gray-500 mt-1">{{ card.label }}</p>
      </Link>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Newest Users -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-900">Newest Registrations</h2>
          <Link href="/admin/users" class="text-sm text-gray-600 hover:text-gray-800">View all →</Link>
        </div>
        <div class="space-y-3">
          <div
            v-for="user in newest_users"
            :key="user.id"
            class="flex items-center gap-3"
          >
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
              {{ user.name.charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
            </div>
            <Link :href="`/admin/users/${user.id}`" class="text-xs text-gray-600 hover:underline flex-shrink-0">View</Link>
          </div>
          <p v-if="!newest_users.length" class="text-sm text-gray-400 text-center py-4">No users yet.</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3">
          <Link
            href="/admin/questionnaires/create"
            class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-pink-300 hover:bg-pink-50 transition-colors text-center"
          >
            <span class="text-2xl">📝</span>
            <span class="text-xs font-medium text-gray-700">New Questionnaire</span>
          </Link>
          <Link
            href="/admin/challenges"
            class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-pink-300 hover:bg-pink-50 transition-colors text-center"
          >
            <span class="text-2xl">🎯</span>
            <span class="text-xs font-medium text-gray-700">Manage Challenges</span>
          </Link>
          <Link
            href="/admin/feature-flags"
            class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-pink-300 hover:bg-pink-50 transition-colors text-center"
          >
            <span class="text-2xl">🚩</span>
            <span class="text-xs font-medium text-gray-700">Feature Flags</span>
          </Link>
          <Link
            href="/admin/audit-logs"
            class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-pink-300 hover:bg-pink-50 transition-colors text-center"
          >
            <span class="text-2xl">🔍</span>
            <span class="text-xs font-medium text-gray-700">Audit Logs</span>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
