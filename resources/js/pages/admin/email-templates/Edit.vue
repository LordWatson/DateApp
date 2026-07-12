<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  template: {
    id: number
    key: string
    label: string
    subject: string
    html_body: string
    text_body: string | null
    active: boolean
  }
}>()

const form = useForm({
  subject: props.template.subject,
  html_body: props.template.html_body,
  text_body: props.template.text_body ?? '',
  active: props.template.active,
})

const save = () => {
  form.put(route('admin.email-templates.update', props.template.id))
}

const preview = () => {
  const win = window.open('', '_blank')

  if (win) {
    win.document.write(form.html_body)
    win.document.close()
  }
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <Link :href="route('admin.email-templates.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Email Templates</Link>
      <h1 class="text-2xl font-bold text-gray-900">{{ template.label }}</h1>
    </div>

    <form @submit.prevent="save" class="space-y-6">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">Template Settings</h2>
          <div class="flex items-center gap-2">
            <input id="active" v-model="form.active" type="checkbox" class="rounded" />
            <label for="active" class="text-sm text-gray-700">Active</label>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-gray-600">Subject Line</label>
          <input
            v-model="form.subject"
            required
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
          />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-medium text-gray-600">HTML Body</label>
            <button type="button" @click="preview" class="text-xs text-pink-500 hover:underline">Preview →</button>
          </div>
          <textarea
            v-model="form.html_body"
            rows="20"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-pink-300"
          />
        </div>

        <div>
          <label class="text-xs font-medium text-gray-600">Plain Text Body (optional)</label>
          <textarea
            v-model="form.text_body"
            rows="6"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
          />
        </div>
      </div>

      <div class="flex gap-3">
        <button
          type="submit"
          :disabled="form.processing"
          class="bg-pink-500 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50"
        >
          Save Template
        </button>
        <Link :href="route('admin.email-templates.index')" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">
          Cancel
        </Link>
      </div>
    </form>
  </div>
</template>
