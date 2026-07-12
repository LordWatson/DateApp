<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

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

function openCreate(): void {
    editingMoment.value = null;
    form.reset();
    form.date = new Date().toISOString().split('T')[0];
    tagInput.value = '';
    showForm.value = true;
}

function openEdit(moment: Moment): void {
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

function addTag(): void {
    const tag = tagInput.value.trim();

    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
    }

    tagInput.value = '';
}

function removeTag(tag: string): void {
    form.tags = form.tags.filter((t) => t !== tag);
}

function submit(): void {
    if (editingMoment.value) {
        form.put(route('moments.update', editingMoment.value.id), {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post(route('moments.store'), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
}

function deleteMoment(moment: Moment): void {
    router.delete(route('moments.destroy', moment.id));
}

function toggleFavourite(moment: Moment): void {
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
    <Head title="Moments" />

    <div class="space-y-4 px-4 py-6 pb-24">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">📖 Moments</h1>
                <p class="text-sm text-muted-foreground">Your beautiful memories together</p>
            </div>
            <PrimaryButton size="sm" @click="openCreate">+ Capture</PrimaryButton>
        </div>

        <!-- Moments List -->
        <div v-if="moments.length > 0" class="space-y-4">
            <div
                v-for="moment in moments"
                :key="moment.id"
                class="card-premium group overflow-hidden"
            >
                <!-- Photo -->
                <div v-if="moment.photo" class="h-48 overflow-hidden">
                    <img :src="moment.photo" :alt="moment.title" class="h-full w-full object-cover" />
                </div>

                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span v-if="moment.mood" class="text-xl" aria-hidden="true">{{ moment.mood }}</span>
                                <h3 class="font-semibold text-foreground">{{ moment.title }}</h3>
                            </div>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ formatDate(moment.date) }}</p>
                            <p v-if="moment.description" class="mt-2 text-sm text-foreground/80">{{ moment.description }}</p>

                            <!-- Tags -->
                            <div v-if="moment.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
                                <span
                                    v-for="tag in moment.tags"
                                    :key="tag"
                                    class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary"
                                >
                                    #{{ tag }}
                                </span>
                            </div>

                            <!-- Linked Plan -->
                            <p v-if="moment.date_night_plan" class="mt-2 text-xs text-muted-foreground">
                                🌙 {{ moment.date_night_plan.theme }}
                            </p>
                        </div>

                        <button
                            class="shrink-0 text-xl transition-transform hover:scale-125 focus:outline-none"
                            :class="moment.is_favourite ? 'text-warning' : 'text-muted-foreground/40'"
                            :aria-label="moment.is_favourite ? 'Remove from favourites' : 'Add to favourites'"
                            @click="toggleFavourite(moment)"
                        >
                            ⭐
                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex gap-3 border-t border-border pt-3">
                        <button
                            class="text-sm font-semibold text-primary transition-opacity hover:opacity-70"
                            @click="openEdit(moment)"
                        >
                            Edit
                        </button>
                        <button
                            class="text-sm font-semibold text-destructive transition-opacity hover:opacity-70"
                            @click="deleteMoment(moment)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            emoji="📖"
            title="No moments yet"
            description="Start capturing your beautiful memories together."
            action-label="Capture First Moment"
            @action="openCreate"
        />
    </div>

    <!-- Moment Form Modal -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center"
                role="dialog"
                aria-modal="true"
                aria-label="Capture a moment"
                @click.self="showForm = false"
            >
                <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-card shadow-2xl">
                    <div class="max-h-[85vh] overflow-y-auto p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-foreground">
                                {{ editingMoment ? 'Edit Moment' : 'Capture a Moment' }}
                            </h2>
                            <button
                                class="flex h-8 w-8 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus:outline-none"
                                aria-label="Close"
                                @click="showForm = false"
                            >
                                ✕
                            </button>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <label class="mb-1 block text-sm font-semibold text-foreground" for="moment-title">Title *</label>
                                <input
                                    id="moment-title"
                                    v-model="form.title"
                                    type="text"
                                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    placeholder="What happened?"
                                    required
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-foreground">Mood</label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="mood in moodOptions"
                                        :key="mood"
                                        type="button"
                                        class="rounded-xl p-2 text-2xl transition-all hover:scale-110 focus:outline-none"
                                        :class="form.mood === mood ? 'bg-primary/10 ring-2 ring-primary' : 'bg-muted'"
                                        :aria-pressed="form.mood === mood"
                                        @click="form.mood = form.mood === mood ? '' : mood"
                                    >
                                        {{ mood }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-foreground" for="moment-date">Date *</label>
                                <input
                                    id="moment-date"
                                    v-model="form.date"
                                    type="date"
                                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    required
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-foreground" for="moment-description">Description</label>
                                <textarea
                                    id="moment-description"
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    placeholder="Tell the story..."
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-foreground">Tags</label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="tagInput"
                                        type="text"
                                        class="flex-1 rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                        placeholder="Add a tag..."
                                        @keydown.enter.prevent="addTag"
                                    />
                                    <button
                                        type="button"
                                        class="rounded-2xl bg-primary/10 px-4 py-3 text-sm font-semibold text-primary transition-colors hover:bg-primary/20"
                                        @click="addTag"
                                    >
                                        Add
                                    </button>
                                </div>
                                <div v-if="form.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in form.tags"
                                        :key="tag"
                                        class="flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary"
                                    >
                                        #{{ tag }}
                                        <button
                                            type="button"
                                            class="text-primary/60 hover:text-primary focus:outline-none"
                                            :aria-label="`Remove tag ${tag}`"
                                            @click="removeTag(tag)"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-foreground" for="moment-notes">Private Notes</label>
                                <textarea
                                    id="moment-notes"
                                    v-model="form.private_notes"
                                    rows="2"
                                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    placeholder="Just for you..."
                                />
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    id="favourite"
                                    v-model="form.is_favourite"
                                    type="checkbox"
                                    class="h-5 w-5 rounded accent-primary"
                                />
                                <label for="favourite" class="text-sm font-semibold text-foreground">Mark as favourite ⭐</label>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <SecondaryButton
                                    type="button"
                                    variant="outline"
                                    full-width
                                    @click="showForm = false"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton type="submit" full-width :loading="form.processing">
                                    {{ editingMoment ? 'Save Changes' : 'Capture Moment' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
