<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

interface QuestionOption {
  id: number
  title: string
  description: string | null
  emoji: string | null
  value: string
  display_order: number
}

interface Question {
  id: number
  title: string
  description: string | null
  emoji: string | null
  type: string
  display_order: number
  required: boolean
  min_value: number | null
  max_value: number | null
  options: QuestionOption[]
}

interface Questionnaire {
  id: number
  title: string
  description: string | null
  emoji: string | null
  status: string
  visibility: string
  estimated_minutes: number | null
  display_order: number
  is_seasonal: boolean
  active_from: string | null
  active_until: string | null
  questions: Question[]
}

const props = defineProps<{ questionnaire: Questionnaire }>()

const form = useForm({
  title: props.questionnaire.title,
  description: props.questionnaire.description ?? '',
  emoji: props.questionnaire.emoji ?? '',
  status: props.questionnaire.status,
  visibility: props.questionnaire.visibility,
  estimated_minutes: props.questionnaire.estimated_minutes,
  display_order: props.questionnaire.display_order,
  is_seasonal: props.questionnaire.is_seasonal,
  active_from: props.questionnaire.active_from ?? '',
  active_until: props.questionnaire.active_until ?? '',
})

const save = () => {
  form.put(route('admin.questionnaires.update', props.questionnaire.id))
}

// New question form
const showNewQuestion = ref(false)
const newQuestion = useForm({
  title: '',
  description: '',
  emoji: '',
  type: 'single_choice',
  display_order: props.questionnaire.questions.length,
  required: true,
  min_value: null as number | null,
  max_value: null as number | null,
})

const addQuestion = () => {
  newQuestion.post(route('admin.questions.store', props.questionnaire.id), {
    onSuccess: () => {
      showNewQuestion.value = false
      newQuestion.reset()
    },
  })
}

const deleteQuestion = (questionId: number) => {
  router.delete(route('admin.questions.destroy', questionId))
}

// New option form
const showNewOption = ref<number | null>(null)
const newOption = useForm({
  title: '',
  description: '',
  emoji: '',
  value: '',
  display_order: 0,
})

const addOption = (questionId: number) => {
  newOption.post(route('admin.options.store', questionId), {
    onSuccess: () => {
      showNewOption.value = null
      newOption.reset()
    },
  })
}

const deleteOption = (optionId: number) => {
  router.delete(route('admin.options.destroy', optionId))
}

const duplicateOption = (optionId: number) => {
  router.post(route('admin.options.duplicate', optionId))
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <Link :href="route('admin.questionnaires.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Questionnaires</Link>
      <h1 class="text-2xl font-bold text-gray-900">Edit: {{ questionnaire.title }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Settings -->
      <div class="lg:col-span-1">
        <form @submit.prevent="save" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
          <h2 class="font-semibold text-gray-900">Settings</h2>

          <div class="grid grid-cols-4 gap-3">
            <div>
              <label class="text-xs text-gray-500">Emoji</label>
              <input v-model="form.emoji" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300" />
            </div>
            <div class="col-span-3">
              <label class="text-xs text-gray-500">Title</label>
              <input v-model="form.title" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300" />
            </div>
          </div>

          <div>
            <label class="text-xs text-gray-500">Description</label>
            <textarea v-model="form.description" rows="2" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-500">Status</label>
              <select v-model="form.status" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300">
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-500">Visibility</label>
              <select v-model="form.visibility" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300">
                <option value="public">Public</option>
                <option value="private">Private</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-500">Est. Minutes</label>
              <input v-model.number="form.estimated_minutes" type="number" min="1" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300" />
            </div>
            <div>
              <label class="text-xs text-gray-500">Order</label>
              <input v-model.number="form.display_order" type="number" min="0" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300" />
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input id="seasonal" v-model="form.is_seasonal" type="checkbox" class="rounded" />
            <label for="seasonal" class="text-xs text-gray-700">Seasonal</label>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-pink-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50"
          >
            Save Settings
          </button>
        </form>
      </div>

      <!-- Questions Builder -->
      <div class="lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">Questions ({{ questionnaire.questions.length }})</h2>
          <button
            @click="showNewQuestion = !showNewQuestion"
            class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors"
          >
            + Add Question
          </button>
        </div>

        <!-- New Question Form -->
        <div v-if="showNewQuestion" class="bg-pink-50 rounded-2xl border border-pink-100 p-5 space-y-3">
          <h3 class="font-medium text-gray-900 text-sm">New Question</h3>
          <div class="grid grid-cols-4 gap-3">
            <div>
              <label class="text-xs text-gray-500">Emoji</label>
              <input v-model="newQuestion.emoji" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
            </div>
            <div class="col-span-3">
              <label class="text-xs text-gray-500">Title</label>
              <input v-model="newQuestion.title" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-500">Type</label>
              <select v-model="newQuestion.type" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white">
                <option value="single_choice">Single Choice</option>
                <option value="multiple_choice">Multiple Choice</option>
                <option value="slider">Slider</option>
                <option value="text">Text</option>
                <option value="textarea">Textarea</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-500">Order</label>
              <input v-model.number="newQuestion.display_order" type="number" min="0" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input id="required" v-model="newQuestion.required" type="checkbox" class="rounded" />
            <label for="required" class="text-xs text-gray-700">Required</label>
          </div>
          <div class="flex gap-2">
            <button
              @click="addQuestion"
              :disabled="newQuestion.processing"
              class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50"
            >
              Add Question
            </button>
            <button
              @click="showNewQuestion = false"
              class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>

        <!-- Questions List -->
        <div
          v-for="question in questionnaire.questions"
          :key="question.id"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-2">
              <span v-if="question.emoji" class="text-xl">{{ question.emoji }}</span>
              <div>
                <p class="font-medium text-gray-900 text-sm">{{ question.title }}</p>
                <span class="text-xs text-gray-400 capitalize">{{ question.type.replace('_', ' ') }} · Order {{ question.display_order }}</span>
              </div>
            </div>
            <button @click="deleteQuestion(question.id)" class="text-xs text-red-400 hover:text-red-600">Delete</button>
          </div>

          <!-- Options -->
          <div v-if="['single_choice', 'multiple_choice'].includes(question.type)" class="mt-3 space-y-2">
            <div
              v-for="option in question.options"
              :key="option.id"
              class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2"
            >
              <span v-if="option.emoji" class="text-sm">{{ option.emoji }}</span>
              <span class="text-sm text-gray-700 flex-1">{{ option.title }}</span>
              <button @click="duplicateOption(option.id)" class="text-xs text-blue-400 hover:text-blue-600">Copy</button>
              <button @click="deleteOption(option.id)" class="text-xs text-red-400 hover:text-red-600">×</button>
            </div>

            <!-- Add Option -->
            <div v-if="showNewOption === question.id" class="bg-gray-50 rounded-xl p-3 space-y-2">
              <div class="grid grid-cols-4 gap-2">
                <input v-model="newOption.emoji" placeholder="😊" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
                <input v-model="newOption.title" placeholder="Option title" class="col-span-2 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
                <input v-model="newOption.value" placeholder="value" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300 bg-white" />
              </div>
              <div class="flex gap-2">
                <button @click="addOption(question.id)" class="bg-pink-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-pink-600 transition-colors">Add</button>
                <button @click="showNewOption = null" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-gray-300 transition-colors">Cancel</button>
              </div>
            </div>

            <button
              v-if="showNewOption !== question.id"
              @click="showNewOption = question.id"
              class="text-xs text-gray-600 hover:underline"
            >
              + Add Option
            </button>
          </div>
        </div>

        <p v-if="!questionnaire.questions.length" class="text-sm text-gray-400 text-center py-8 bg-white rounded-2xl border border-gray-100">
          No questions yet. Add your first question above.
        </p>
      </div>
    </div>
  </div>
</template>
