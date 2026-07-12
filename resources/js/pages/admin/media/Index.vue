<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import AdminLayout from '@/layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps<{
  media: {
    data: Array<{
      id: number
      original_filename: string
      url: string
      mime_type: string
      size: number
      folder: string | null
      created_at: string
    }>
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
  folders: string[]
  filters: { search?: string; folder?: string }
}>()

const uploadForm = useForm({
  file: null as File | null,
  folder: 'uploads',
  alt: '',
})

const fileInput = ref<HTMLInputElement | null>(null)
const confirmDelete = ref<number | null>(null)

const onFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  uploadForm.file = target.files?.[0] ?? null
}

const doUpload = () => {
  uploadForm.post(route('admin.media.store'), {
    onSuccess: () => {
      uploadForm.reset()

      if (fileInput.value) {
        fileInput.value.value = ''
      }
    },
  })
}

const doDelete = (id: number) => {
  router.delete(route('admin.media.destroy', id), {
    onSuccess: () => {
      confirmDelete.value = null
    },
  })
}

const humanSize = (bytes: number): string => {
  if (bytes >= 1048576) {
    return `${(bytes / 1048576).toFixed(1)} MB`
  }

  if (bytes >= 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }

  return `${bytes} B`
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Media Library</h1>
        <p class="text-gray-500 text-sm mt-1">{{ media.total }} files</p>
      </div>
    </div>

    <!-- Upload -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
      <h2 class="font-semibold text-gray-900 mb-4">Upload File</h2>
      <div class="flex flex-wrap gap-3 items-end">
        <div>
          <label class="text-xs text-gray-500">File</label>
          <input
            ref="fileInput"
            type="file"
            @change="onFileChange"
            class="block mt-1 text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100"
          />
        </div>
        <div>
          <label class="text-xs text-gray-500">Folder</label>
          <input
            v-model="uploadForm.folder"
            class="block mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
            placeholder="uploads"
          />
        </div>
        <div>
          <label class="text-xs text-gray-500">Alt Text</label>
          <input
            v-model="uploadForm.alt"
            class="block mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-pink-300"
            placeholder="Description…"
          />
        </div>
        <button
          @click="doUpload"
          :disabled="!uploadForm.file || uploadForm.processing"
          class="bg-pink-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-pink-600 transition-colors disabled:opacity-50"
        >
          Upload
        </button>
      </div>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
      <div
        v-for="item in media.data"
        :key="item.id"
        class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden group"
      >
        <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
          <img
            v-if="item.mime_type.startsWith('image/')"
            :src="item.url"
            :alt="item.original_filename"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-3xl">📄</span>
        </div>
        <div class="p-2">
          <p class="text-xs text-gray-700 truncate font-medium">{{ item.original_filename }}</p>
          <p class="text-xs text-gray-400">{{ humanSize(item.size) }}</p>
          <div class="flex gap-2 mt-1">
            <a :href="item.url" target="_blank" class="text-xs text-pink-500 hover:underline">View</a>
            <button @click="confirmDelete = item.id" class="text-xs text-red-400 hover:underline">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <p v-if="!media.data.length" class="text-sm text-gray-400 text-center py-12 bg-white rounded-2xl border border-gray-100 mt-4">
      No media files yet. Upload your first file above.
    </p>

    <!-- Delete Modal -->
    <div v-if="confirmDelete" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-gray-900 mb-2">Delete File?</h3>
        <p class="text-sm text-gray-500 mb-6">This will permanently delete the file from storage.</p>
        <div class="flex gap-3">
          <button @click="doDelete(confirmDelete!)" class="flex-1 bg-red-500 text-white py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition-colors">Delete</button>
          <button @click="confirmDelete = null" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>
