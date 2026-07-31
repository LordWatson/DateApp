<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  user: {
    id: number
    name: string
    email: string
    display_name: string | null
    gender: string | null
    date_of_birth: string | null
    timezone: string
    is_suspended: boolean
    suspended_at: string | null
    suspension_reason: string | null
    onboarding_completed: boolean
    current_streak: number
    longest_streak: number
    monthly_completion_count: number
    created_at: string
    role: { id: number; label: string } | null
    partner: { id: number; name: string; email: string } | null
    responses: Array<{ id: number; questionnaire: { title: string }; completed_at: string | null; created_at: string }>
    achievements: Array<{ id: number; name: string; emoji: string; pivot: { unlocked_at: string } }>
  }
}>()

const editForm = useForm({
  name: props.user.name,
  email: props.user.email,
  role_id: props.user.role?.id ?? null,
})

const editing = ref(false)

const save = () => {
  editForm.put(route('admin.users.update', props.user.id), {
    onSuccess: () => {
      editing.value = false
    },
  })
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <Link :href="route('admin.users.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Users</Link>
      <h1 class="text-2xl font-bold text-gray-900">{{ user.name }}</h1>
      <span
        :class="[
          'text-xs px-2 py-1 rounded-full font-medium',
          user.is_suspended ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700',
        ]"
      >
        {{ user.is_suspended ? 'Suspended' : 'Active' }}
      </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Profile Card -->
      <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Profile</h2>
            <button
              @click="editing = !editing"
              class="text-xs text-gray-600 hover:underline"
            >
              {{ editing ? 'Cancel' : 'Edit' }}
            </button>
          </div>

          <div v-if="!editing" class="space-y-3 text-sm">
            <div>
              <p class="text-gray-500 text-xs">Name</p>
              <p class="font-medium text-gray-900">{{ user.name }}</p>
            </div>
            <div>
              <p class="text-gray-500 text-xs">Email</p>
              <p class="font-medium text-gray-900">{{ user.email }}</p>
            </div>
            <div>
              <p class="text-gray-500 text-xs">Role</p>
              <p class="font-medium text-gray-900">{{ user.role?.label ?? 'User' }}</p>
            </div>
            <div>
              <p class="text-gray-500 text-xs">Joined</p>
              <p class="font-medium text-gray-900">{{ new Date(user.created_at).toLocaleDateString() }}</p>
            </div>
            <div v-if="user.partner">
              <p class="text-gray-500 text-xs">Partner</p>
              <Link :href="route('admin.users.show', user.partner.id)" class="font-medium text-gray-600 hover:underline">
                {{ user.partner.name }}
              </Link>
            </div>
          </div>

          <form v-else @submit.prevent="save" class="space-y-3">
            <div>
              <label class="text-xs text-gray-500">Name</label>
              <input
                v-model="editForm.name"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
              />
            </div>
            <div>
              <label class="text-xs text-gray-500">Email</label>
              <input
                v-model="editForm.email"
                type="email"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
              />
            </div>
            <button
              type="submit"
              class="w-full bg-pink-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
            >
              Save Changes
            </button>
          </form>
        </div>

        <!-- Stats -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <h2 class="font-semibold text-gray-900 mb-4">Stats</h2>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-pink-50 rounded-xl p-3 text-center">
              <p class="text-2xl font-bold text-pink-600">{{ user.current_streak }}</p>
              <p class="text-xs text-gray-500 mt-1">Current Streak</p>
            </div>
            <div class="bg-purple-50 rounded-xl p-3 text-center">
              <p class="text-2xl font-bold text-purple-600">{{ user.longest_streak }}</p>
              <p class="text-xs text-gray-500 mt-1">Longest Streak</p>
            </div>
          </div>
        </div>

        <!-- Suspension info -->
        <div v-if="user.is_suspended" class="bg-red-50 rounded-2xl border border-red-100 p-6">
          <h2 class="font-semibold text-red-800 mb-2">Suspended</h2>
          <p class="text-xs text-red-600">{{ user.suspension_reason ?? 'No reason provided.' }}</p>
          <p class="text-xs text-red-400 mt-1">{{ user.suspended_at ? new Date(user.suspended_at).toLocaleDateString() : '' }}</p>
        </div>
      </div>

      <!-- Right column -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Questionnaire Responses -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <h2 class="font-semibold text-gray-900 mb-4">Questionnaire Responses</h2>
          <div class="space-y-2">
            <div
              v-for="response in user.responses"
              :key="response.id"
              class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0"
            >
              <p class="text-sm font-medium text-gray-900">{{ response.questionnaire?.title }}</p>
              <span
                :class="[
                  'text-xs px-2 py-1 rounded-full',
                  response.completed_at ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700',
                ]"
              >
                {{ response.completed_at ? 'Completed' : 'In Progress' }}
              </span>
            </div>
            <p v-if="!user.responses.length" class="text-sm text-gray-400 text-center py-4">No responses yet.</p>
          </div>
        </div>

        <!-- Achievements -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
          <h2 class="font-semibold text-gray-900 mb-4">Achievements</h2>
          <div class="flex flex-wrap gap-2">
            <div
              v-for="achievement in user.achievements"
              :key="achievement.id"
              class="flex items-center gap-2 bg-yellow-50 rounded-xl px-3 py-2"
            >
              <span class="text-lg">{{ achievement.emoji }}</span>
              <span class="text-xs font-medium text-gray-700">{{ achievement.name }}</span>
            </div>
            <p v-if="!user.achievements.length" class="text-sm text-gray-400">No achievements yet.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
