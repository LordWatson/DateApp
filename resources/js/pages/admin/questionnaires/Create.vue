<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const form = useForm({
  title: '',
  description: '',
  emoji: '',
  status: 'draft',
  visibility: 'public',
  estimated_minutes: null as number | null,
  display_order: 0,
  is_seasonal: false,
  active_from: '',
  active_until: '',
})

const submit = () => {
  form.post(route('admin.questionnaires.store'))
}
</script>

<template>
  <div class="max-w-2xl">
    <div class="flex items-center gap-4 mb-6">
      <Link :href="route('admin.questionnaires.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Questionnaires</Link>
      <h1 class="text-2xl font-bold text-gray-900">New Questionnaire</h1>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
      <div class="grid grid-cols-4 gap-4">
        <div>
          <label class="text-xs font-medium text-gray-600">Emoji</label>
          <input
            v-model="form.emoji"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
            placeholder="💕"
          />
        </div>
        <div class="col-span-3">
          <label class="text-xs font-medium text-gray-600">Title *</label>
          <input
            v-model="form.title"
            required
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
            placeholder="Questionnaire title"
          />
          <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
        </div>
      </div>

      <div>
        <label class="text-xs font-medium text-gray-600">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
          placeholder="Brief description…"
        />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-xs font-medium text-gray-600">Status</label>
          <select v-model="form.status" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300">
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Visibility</label>
          <select v-model="form.visibility" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300">
            <option value="public">Public</option>
            <option value="private">Private</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-xs font-medium text-gray-600">Estimated Minutes</label>
          <input
            v-model.number="form.estimated_minutes"
            type="number"
            min="1"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
          />
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Display Order</label>
          <input
            v-model.number="form.display_order"
            type="number"
            min="0"
            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300"
          />
        </div>
      </div>

      <div class="flex items-center gap-3">
        <input id="seasonal" v-model="form.is_seasonal" type="checkbox" class="rounded" />
        <label for="seasonal" class="text-sm text-gray-700">Seasonal questionnaire</label>
      </div>

      <div v-if="form.is_seasonal" class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-xs font-medium text-gray-600">Active From</label>
          <input v-model="form.active_from" type="date" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300" />
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600">Active Until</label>
          <input v-model="form.active_until" type="date" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300" />
        </div>
      </div>

      <div class="flex gap-3 pt-2">
        <button
          type="submit"
          :disabled="form.processing"
          class="bg-pink-500 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50"
        >
          Create Questionnaire
        </button>
        <Link :href="route('admin.questionnaires.index')" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">
          Cancel
        </Link>
      </div>
    </form>
  </div>
</template>
