<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

defineProps<{
  links: Array<{ url: string | null; label: string; active: boolean }>
}>()
</script>

<template>
  <div class="flex gap-1 mt-4 flex-wrap">
    <Link
      v-for="link in links"
      :key="link.label"
      :href="link.url ?? '#'"
      :class="[
        'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
        link.active ? 'bg-pink-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50',
        !link.url ? 'opacity-40 pointer-events-none' : '',
      ]"
    >
      {{ link.label }}
    </Link>
  </div>
</template>
