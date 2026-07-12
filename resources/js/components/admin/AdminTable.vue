<script setup lang="ts">
defineProps<{
  columns: Array<{ key: string; label: string; sortable?: boolean }>
  empty?: string
}>()
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 bg-gray-50">
            <th
              v-for="col in columns"
              :key="col.key"
              class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <slot />
          <tr v-if="$slots.empty || empty">
            <td :colspan="columns.length" class="px-4 py-12 text-center text-gray-400">
              <slot name="empty">{{ empty ?? 'No records found.' }}</slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
