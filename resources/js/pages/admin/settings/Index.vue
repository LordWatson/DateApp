<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

interface Setting {
  id: number
  key: string
  label: string
  description: string | null
  value: string | null
  type: string
  group: string
}

const props = defineProps<{
  settings: Record<string, Setting[]>
}>()

// Build local editable copy
const localValues = ref<Record<number, string | null>>({})

Object.values(props.settings).flat().forEach((s) => {
  localValues.value[s.id] = s.value
})

const saving = ref(false)

const saveAll = () => {
  saving.value = true
  const payload = Object.entries(localValues.value).map(([id, value]) => ({ id: Number(id), value }))

  router.post(route('admin.settings.bulk-update'), { settings: payload }, {
    onFinish: () => {
      saving.value = false
    },
  })
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
        <p class="text-gray-500 text-sm mt-1">Configure application-wide settings.</p>
      </div>
      <button
        @click="saveAll"
        :disabled="saving"
        class="bg-pink-500 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50"
      >
        {{ saving ? 'Saving…' : 'Save All' }}
      </button>
    </div>

    <div v-for="(groupSettings, groupName) in settings" :key="groupName" class="mb-8">
      <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3 capitalize">{{ groupName }}</h2>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-50">
        <div
          v-for="setting in groupSettings"
          :key="setting.id"
          class="flex items-center gap-6 px-6 py-4"
        >
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900 text-sm">{{ setting.label }}</p>
            <p v-if="setting.description" class="text-xs text-gray-500 mt-0.5">{{ setting.description }}</p>
          </div>
          <div class="w-64 flex-shrink-0">
            <input
              v-if="setting.type === 'color'"
              v-model="localValues[setting.id]"
              type="color"
              class="w-full h-10 rounded-xl border border-gray-200 cursor-pointer"
            />
            <input
              v-else-if="setting.type === 'boolean'"
              v-model="localValues[setting.id]"
              type="checkbox"
              class="rounded"
            />
            <input
              v-else
              v-model="localValues[setting.id]"
              :type="setting.type === 'integer' ? 'number' : 'text'"
              class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
