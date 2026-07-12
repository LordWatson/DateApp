<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Moment {
    id: number;
    title: string;
    description: string | null;
    mood: string | null;
    photo: string | null;
    date: string;
    is_favourite: boolean;
    private_notes: string | null;
    tags: string[];
    date_night_plan: { id: number; theme: string } | null;
}

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
}

defineProps<{
    moments: Moment[];
    pagination: Pagination;
}>();

const showForm = ref(false);
const editingMoment = ref<Moment | null>(null);
const tagInput = ref('');

const moodOptions = ['😍', '😊', '🥰', '😂', '😌', '🔥', '💕', '✨', '🌟', '💫'];

const form = useForm({
    title: '',
    description: '',
    mood: '',
    date: new Date().toISOString().split('T')[0],
    is_favourite: false,
    private_notes: '',
    tags: [] as string[],
    photo: null as File | null,
});

function openCreate() {
    editingMoment.value = null;
    form.reset();
    form.date = new Date().toISOString().split('T')[0];
    tagInput.value = '';
    showForm.value = true;
}

function openEdit(moment: Moment) {
    editingMoment.value = moment;
    form.title = moment.title;
    form.description = moment.description ?? '';
    form.mood = moment.mood ?? '';
    form.date = moment.date;
    form.is_favourite = moment.is_favourite;
    form.private_notes = moment.private_notes ?? '';
    form.tags = [...moment.tags];
    showForm.value = true;
}

function addTag() {
    const tag = tagInput.value.trim();

    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
    }

    tagInput.value = '';
}

function removeTag(tag: string) {
    form.tags = form.tags.filter((t) => t !== tag);
}

function submit() {
    if (editingMoment.value) {
        form.put(route('moments.update', editingMoment.value.id), {
            onSuccess: () => {
 showForm.value = false;
},
        });
    } else {
        form.post(route('moments.store'), {
            onSuccess: () => {
 showForm.value = false; form.reset();
},
        });
    }
}

function deleteMoment(moment: Moment) {
    if (confirm('Delete this moment?')) {
        router.delete(route('moments.destroy', moment.id));
    }
}

function toggleFavourite(moment: Moment) {
    router.post(route('moments.toggle-favourite', moment.id));
}

function formatDate(dateStr: string): string {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Moments" />

        <div class="min-h-screen bg-[#FFF7FB] px-4 py-8">
            <div class="mx-auto max-w-lg">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">📖 Moments</h1>
                        <p class="text-sm text-gray-500">Your beautiful memories together</p>
                    </div>
                    <PrimaryButton @click="openCreate">+ Capture</PrimaryButton>
                </div>

                <!-- Timeline -->
                <div v-if="moments.length > 0" class="space-y-4">
                    <div
                        v-for="moment in moments"
                        :key="moment.id"
                        class="group relative overflow-hidden rounded-3xl bg-white shadow-xl transition-all hover:shadow-2xl"
                    >
                        <!-- Photo -->
                        <div v-if="moment.photo" class="h-48 overflow-hidden">
                            <img :src="moment.photo" :alt="moment.title" class="h-full w-full object-cover" />
                        </div>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span v-if="moment.mood" class="text-xl">{{ moment.mood }}</span>
                                        <h3 class="font-semibold text-gray-900">{{ moment.title }}</h3>
                                        <span v-if="moment.is_favourite" class="text-yellow-400">⭐</span>
                                    </div>
                                    <div class="mt-0.5 text-sm text-gray-400">{{ formatDate(moment.date) }}</div>
                                    <p v-if="moment.description" class="mt-2 text-sm text-gray-600">{{ moment.description }}</p>

                                    <!-- Tags -->
                                    <div v-if="moment.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
                                        <span
                                            v-for="tag in moment.tags"
                                            :key="tag"
                                            class="rounded-full bg-pink-50 px-2 py-0.5 text-xs font-semibold text-pink-600"
                                        >
                                            #{{ tag }}
                                        </span>
                                    </div>

                                    <!-- Linked Plan -->
                                    <div v-if="moment.date_night_plan" class="mt-2 text-xs text-gray-400">
                                        🌙 {{ moment.date_night_plan.theme }}
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <button
                                        class="text-xl transition-transform hover:scale-125"
                                        :class="moment.is_favourite ? 'text-yellow-400' : 'text-gray-300'"
                                        @click="toggleFavourite(moment)"
                                    >
                                        ⭐
                                    </button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex gap-3 border-t border-gray-50 pt-3 opacity-0 transition-opacity group-hover:opacity-100">
                                <button class="text-sm font-semibold text-blue-500" @click="openEdit(moment)">Edit</button>
                                <button class="text-sm font-semibold text-red-400" @click="deleteMoment(moment)">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-16 text-center">
                    <div class="mb-4 text-6xl">📖</div>
                    <h3 class="text-xl font-semibold text-gray-900">No moments yet</h3>
                    <p class="mt-2 text-gray-500">Start capturing your beautiful memories</p>
                    <PrimaryButton class="mt-6" @click="openCreate">Capture First Moment</PrimaryButton>
                </div>
            </div>
        </div>

        <!-- Moment Form Modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
                <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <div class="max-h-[85vh] overflow-y-auto p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">
                                {{ editingMoment ? 'Edit Moment' : 'Capture a Moment' }}
                            </h2>
                            <button class="text-gray-400 hover:text-gray-600" @click="showForm = false">✕</button>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Title *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none"
                                    placeholder="What happened?"
                                    required
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-700">Mood</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="mood in moodOptions"
                                        :key="mood"
                                        type="button"
                                        class="rounded-xl p-2 text-2xl transition-all hover:scale-110"
                                        :class="form.mood === mood ? 'bg-pink-100 ring-2 ring-pink-400' : 'bg-gray-50'"
                                        @click="form.mood = form.mood === mood ? '' : mood"
                                    >
                                        {{ mood }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Date *</label>
                                <input
                                    v-model="form.date"
                                    type="date"
                                    class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none"
                                    required
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none"
                                    placeholder="Tell the story..."
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Tags</label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="tagInput"
                                        type="text"
                                        class="flex-1 rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none"
                                        placeholder="Add a tag..."
                                        @keydown.enter.prevent="addTag"
                                    />
                                    <button type="button" class="rounded-2xl bg-pink-100 px-4 py-3 text-sm font-semibold text-pink-600" @click="addTag">Add</button>
                                </div>
                                <div v-if="form.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in form.tags"
                                        :key="tag"
                                        class="flex items-center gap-1 rounded-full bg-pink-50 px-3 py-1 text-sm font-semibold text-pink-600"
                                    >
                                        #{{ tag }}
                                        <button type="button" class="text-pink-400 hover:text-pink-600" @click="removeTag(tag)">×</button>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Private Notes</label>
                                <textarea
                                    v-model="form.private_notes"
                                    rows="2"
                                    class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none"
                                    placeholder="Just for you..."
                                />
                            </div>

                            <div class="flex items-center gap-3">
                                <input id="favourite" v-model="form.is_favourite" type="checkbox" class="h-5 w-5 rounded text-pink-500" />
                                <label for="favourite" class="text-sm font-semibold text-gray-700">Mark as favourite ⭐</label>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    class="flex-1 rounded-2xl border border-gray-200 py-3 font-semibold text-gray-600"
                                    @click="showForm = false"
                                >
                                    Cancel
                                </button>
                                <PrimaryButton type="submit" class="flex-1" :disabled="form.processing">
                                    {{ editingMoment ? 'Save Changes' : 'Capture Moment' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
