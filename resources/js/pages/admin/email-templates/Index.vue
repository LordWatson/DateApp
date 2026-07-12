<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  templates: Array<{
    id: number
    key: string
    label: string
    subject: string
    active: boolean
    updated_at: string
  }>
}>()
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Email Templates</h1>
      <p class="text-gray-500 text-sm mt-1">Manage transactional email templates.</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-50">
      <div
        v-for="template in templates"
        :key="template.id"
        class="flex items-center justify-between px-6 py-4"
      >
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <p class="font-medium text-gray-900 text-sm">{{ template.label }}</p>
            <span
              :class="[
                'text-xs px-2 py-0.5 rounded-full font-medium',
                template.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500',
              ]"
            >
              {{ template.active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <p class="text-xs text-gray-500">Subject: {{ template.subject }}</p>
          <p class="text-xs text-gray-400 font-mono mt-0.5">{{ template.key }}</p>
        </div>
        <div class="flex items-center gap-3 ml-4">
          <span class="text-xs text-gray-400">{{ new Date(template.updated_at).toLocaleDateString() }}</span>
          <Link
            :href="route('admin.email-templates.edit', template.id)"
            class="text-sm text-pink-500 hover:underline font-medium"
          >
            Edit
          </Link>
        </div>
      </div>
      <p v-if="!templates.length" class="px-6 py-12 text-center text-gray-400 text-sm">No email templates found.</p>
    </div>
  </div>
</template>
