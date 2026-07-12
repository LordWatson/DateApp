<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  flags: Record<string, Array<{
    id: number
    key: string
    label: string
    description: string | null
    enabled: boolean
    group: string
  }>>
}>()

const toggle = (id: number, enabled: boolean) => {
  router.put(route('admin.feature-flags.update', id), { enabled: !enabled })
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Feature Flags</h1>
      <p class="text-gray-500 text-sm mt-1">Enable or disable application features without code changes.</p>
    </div>

    <div v-for="(groupFlags, groupName) in flags" :key="groupName" class="mb-8">
      <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3 capitalize">{{ groupName }}</h2>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-50">
        <div
          v-for="flag in groupFlags"
          :key="flag.id"
          class="flex items-center justify-between px-6 py-4"
        >
          <div class="flex-1 min-w-0 mr-4">
            <p class="font-medium text-gray-900 text-sm">{{ flag.label }}</p>
            <p v-if="flag.description" class="text-xs text-gray-500 mt-0.5">{{ flag.description }}</p>
            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ flag.key }}</p>
          </div>
          <button
            @click="toggle(flag.id, flag.enabled)"
            :class="[
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2',
              flag.enabled ? 'bg-pink-500' : 'bg-gray-200',
            ]"
          >
            <span
              :class="[
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                flag.enabled ? 'translate-x-5' : 'translate-x-0',
              ]"
            />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
