<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { X, FileText } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import admin from '@/routes/admin';

defineOptions({ layout: AdminLayout });

defineProps<{
    media: {
        data: Array<{
            id: number;
            original_filename: string;
            url: string;
            mime_type: string;
            size: number;
            folder: string | null;
            created_at: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    folders: string[];
    filters: { search?: string; folder?: string };
}>();

const uploadForm = useForm({
    file: null as File | null,
    folder: 'uploads',
    alt: '',
});

const fileInput = ref<HTMLInputElement | null>(null);
const confirmDelete = ref<number | null>(null);

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    uploadForm.file = target.files?.[0] ?? null;
};

const doUpload = () => {
    uploadForm.post(admin.media.store().url, {
        onSuccess: () => {
            uploadForm.reset();

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const doDelete = (id: number) => {
    router.delete(admin.media.destroy(id).url, {
        onSuccess: () => {
            confirmDelete.value = null;
        },
    });
};

const humanSize = (bytes: number): string => {
    if (bytes >= 1048576) {
        return `${(bytes / 1048576).toFixed(1)} MB`;
    }

    if (bytes >= 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${bytes} B`;
};
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Media library
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ media.total }} files
                </p>
            </div>
        </div>

        <!-- Upload -->
        <div class="mb-6 card-premium rounded-2xl p-6">
            <h2 class="mb-4 text-lg font-semibold text-foreground">
                Upload file
            </h2>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs text-muted-foreground">File</label>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        @change="onFileChange"
                        class="mt-1 block text-sm text-muted-foreground file:mr-3 file:rounded-full file:border-0 file:bg-accent file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary hover:file:bg-muted"
                    />
                </div>
                <div>
                    <label class="text-xs text-muted-foreground">Folder</label>
                    <input
                        v-model="uploadForm.folder"
                        class="mt-1 block input-premium px-3 py-2 text-sm"
                        placeholder="uploads"
                    />
                </div>
                <div>
                    <label class="text-xs text-muted-foreground"
                        >Alt text</label
                    >
                    <input
                        v-model="uploadForm.alt"
                        class="mt-1 block input-premium px-3 py-2 text-sm"
                        placeholder="Description…"
                    />
                </div>
                <Button
                    size="sm"
                    @click="doUpload"
                    :disabled="!uploadForm.file || uploadForm.processing"
                >
                    Upload
                </Button>
            </div>
            <p class="mt-3 text-xs text-muted-foreground">
                Images only (JPG, PNG, GIF or WebP), up to 10 MB.
            </p>
            <p
                v-if="uploadForm.errors.file"
                class="mt-1 text-xs text-destructive"
                role="alert"
            >
                {{ uploadForm.errors.file }}
            </p>
        </div>

        <!-- Grid -->
        <div
            class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
        >
            <div
                v-for="item in media.data"
                :key="item.id"
                class="group overflow-hidden card-premium rounded-2xl"
            >
                <div
                    class="flex aspect-square items-center justify-center overflow-hidden bg-muted"
                >
                    <img
                        v-if="item.mime_type.startsWith('image/')"
                        :src="item.url"
                        :alt="item.original_filename"
                        class="h-full w-full object-cover"
                    />
                    <FileText
                        v-else
                        class="size-8 text-muted-foreground"
                        aria-hidden="true"
                    />
                </div>
                <div class="p-2">
                    <p class="truncate text-xs font-medium text-foreground">
                        {{ item.original_filename }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ humanSize(item.size) }}
                    </p>
                    <div class="mt-1 flex gap-2">
                        <a
                            :href="item.url"
                            target="_blank"
                            class="text-xs font-medium text-foreground hover:underline"
                            >View</a
                        >
                        <button
                            @click="confirmDelete = item.id"
                            class="text-xs text-destructive hover:underline"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p
            v-if="!media.data.length"
            class="mt-4 card-premium rounded-2xl py-12 text-center text-sm text-muted-foreground"
        >
            No media files yet. Upload your first file above.
        </p>

        <!-- Delete Modal -->
        <div
            v-if="confirmDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md card-premium p-6"
                role="dialog"
                aria-modal="true"
            >
                <div class="mb-2 flex items-start justify-between gap-4">
                    <h2 class="text-xl font-semibold text-foreground">
                        Delete file?
                    </h2>
                    <button
                        type="button"
                        class="-mt-1 -mr-1 flex size-8 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Close"
                        @click="confirmDelete = null"
                    >
                        <X class="size-4" aria-hidden="true" />
                    </button>
                </div>
                <p class="mb-6 text-sm text-muted-foreground">
                    This will permanently delete the file from storage.
                </p>
                <div class="flex gap-3">
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="doDelete(confirmDelete!)"
                        class="flex-1"
                        >Delete</Button
                    >
                    <Button
                        variant="outline"
                        size="sm"
                        @click="confirmDelete = null"
                        class="flex-1"
                        >Cancel</Button
                    >
                </div>
            </div>
        </div>
    </div>
</template>
