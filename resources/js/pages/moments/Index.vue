<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Moon, Pencil, Plus, Star, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { store, update, show } from '@/routes/moments';

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

const moodOptions = [
    '😍',
    '😊',
    '🥰',
    '😂',
    '😌',
    '🔥',
    '💕',
    '✨',
    '🌟',
    '💫',
];

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
        form.put(update(editingMoment.value.id).url, {
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post(store().url, {
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

const labelClass = 'mb-1.5 block text-[13px] font-medium text-foreground/80';
const focusClass =
    'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';
</script>

<template>
    <Head title="Moments" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Your story"
            title="Moments"
            description="Your beautiful memories together"
        >
            <PrimaryButton size="sm" @click="openCreate">
                <Plus class="size-4" aria-hidden="true" />
                Capture
            </PrimaryButton>
        </PageHeader>

        <!-- Moments list -->
        <div v-if="moments.length > 0" class="space-y-3">
            <div
                v-for="moment in moments"
                :key="moment.id"
                :class="[
                    'card-hover cursor-pointer overflow-hidden card-premium',
                    focusClass,
                ]"
                role="button"
                :aria-label="`View ${moment.title}`"
                tabindex="0"
                @click="router.visit(show(moment.id).url)"
                @keydown.enter="router.visit(show(moment.id).url)"
            >
                <!-- Photo -->
                <div v-if="moment.photo" class="h-48 overflow-hidden">
                    <img
                        :src="moment.photo"
                        :alt="moment.title"
                        class="h-full w-full object-cover"
                    />
                </div>

                <div class="space-y-4 p-5">
                    <div class="flex items-start gap-3.5">
                        <span
                            v-if="moment.mood"
                            class="icon-tile h-11 w-11 text-xl"
                            aria-hidden="true"
                            >{{ moment.mood }}</span
                        >
                        <div class="min-w-0 flex-1">
                            <h3
                                class="text-lg leading-tight font-semibold text-foreground"
                            >
                                {{ moment.title }}
                            </h3>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ formatDate(moment.date) }}
                            </p>
                        </div>

                        <button
                            :class="[
                                '-mt-1.5 -mr-1.5 flex size-10 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-muted',
                                focusClass,
                                moment.is_favourite
                                    ? 'text-gold'
                                    : 'text-muted-foreground/50',
                            ]"
                            :aria-label="
                                moment.is_favourite
                                    ? 'Remove from favourites'
                                    : 'Add to favourites'
                            "
                            @click.stop="toggleFavourite(moment)"
                        >
                            <Star
                                class="size-5"
                                :class="moment.is_favourite && 'fill-current'"
                                aria-hidden="true"
                            />
                        </button>
                    </div>

                    <p
                        v-if="moment.description"
                        class="text-sm leading-relaxed text-foreground/80"
                    >
                        {{ moment.description }}
                    </p>

                    <!-- Tags -->
                    <div
                        v-if="moment.tags.length > 0"
                        class="flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="tag in moment.tags"
                            :key="tag"
                            class="badge-soft"
                        >
                            #{{ tag }}
                        </span>
                    </div>

                    <!-- Linked plan -->
                    <p
                        v-if="moment.date_night_plan"
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Moon class="size-3.5 text-gold" aria-hidden="true" />
                        {{ moment.date_night_plan.theme }}
                    </p>

                    <!-- Actions -->
                    <div
                        class="flex items-center justify-between border-t border-border pt-2"
                    >
                        <button
                            :class="[
                                '-ml-2 inline-flex min-h-10 items-center gap-1.5 rounded-full px-3 text-[13px] font-medium text-primary transition-colors hover:bg-accent',
                                focusClass,
                            ]"
                            @click.stop="openEdit(moment)"
                        >
                            <Pencil class="size-3.5" aria-hidden="true" />
                            Edit
                        </button>
                        <button
                            :class="[
                                '-mr-2 inline-flex min-h-10 items-center gap-1.5 rounded-full px-3 text-[13px] font-medium text-destructive transition-colors hover:bg-destructive/10',
                                focusClass,
                            ]"
                            @click.stop="deleteMoment(moment)"
                        >
                            <Trash2 class="size-3.5" aria-hidden="true" />
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="card-premium">
            <EmptyState
                emoji="📖"
                title="No moments yet"
                description="Start capturing your beautiful memories together."
                action-label="Capture first moment"
                @action="openCreate"
            />
        </div>
    </div>

    <!-- Moment form modal -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-label="Capture a moment"
                @click.self="showForm = false"
            >
                <div
                    class="w-full max-w-lg overflow-hidden card-premium rounded-b-none sm:rounded-3xl"
                >
                    <div class="max-h-[85vh] overflow-y-auto p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-foreground">
                                {{
                                    editingMoment
                                        ? 'Edit moment'
                                        : 'Capture a moment'
                                }}
                            </h2>
                            <button
                                type="button"
                                :class="[
                                    '-mr-2 flex size-10 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground',
                                    focusClass,
                                ]"
                                aria-label="Close"
                                @click="showForm = false"
                            >
                                <X class="size-5" aria-hidden="true" />
                            </button>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <label :class="labelClass" for="moment-title"
                                    >Title *</label
                                >
                                <input
                                    id="moment-title"
                                    v-model="form.title"
                                    type="text"
                                    class="input-premium"
                                    placeholder="What happened?"
                                    required
                                />
                                <p
                                    v-if="form.errors.title"
                                    class="mt-1 text-sm text-destructive"
                                >
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div role="group" aria-labelledby="moment-mood">
                                <p id="moment-mood" :class="labelClass">Mood</p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="mood in moodOptions"
                                        :key="mood"
                                        type="button"
                                        :class="[
                                            'flex h-10 w-10 items-center justify-center rounded-xl text-xl transition-colors',
                                            focusClass,
                                            form.mood === mood
                                                ? 'bg-accent ring-2 ring-primary'
                                                : 'bg-muted hover:bg-accent',
                                        ]"
                                        :aria-pressed="form.mood === mood"
                                        @click="
                                            form.mood =
                                                form.mood === mood ? '' : mood
                                        "
                                    >
                                        {{ mood }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label :class="labelClass" for="moment-date"
                                    >Date *</label
                                >
                                <input
                                    id="moment-date"
                                    v-model="form.date"
                                    type="date"
                                    class="input-premium"
                                    required
                                />
                                <p
                                    v-if="form.errors.date"
                                    class="mt-1 text-sm text-destructive"
                                >
                                    {{ form.errors.date }}
                                </p>
                            </div>

                            <div>
                                <label
                                    :class="labelClass"
                                    for="moment-description"
                                    >Description</label
                                >
                                <textarea
                                    id="moment-description"
                                    v-model="form.description"
                                    rows="3"
                                    class="input-premium"
                                    placeholder="Tell the story..."
                                />
                            </div>

                            <div>
                                <label :class="labelClass" for="moment-tag"
                                    >Tags</label
                                >
                                <div class="flex gap-2">
                                    <input
                                        id="moment-tag"
                                        v-model="tagInput"
                                        type="text"
                                        class="input-premium flex-1"
                                        placeholder="Add a tag..."
                                        @keydown.enter.prevent="addTag"
                                    />
                                    <SecondaryButton
                                        type="button"
                                        variant="outline"
                                        @click="addTag"
                                    >
                                        Add
                                    </SecondaryButton>
                                </div>
                                <div
                                    v-if="form.tags.length > 0"
                                    class="mt-2 flex flex-wrap gap-1.5"
                                >
                                    <span
                                        v-for="tag in form.tags"
                                        :key="tag"
                                        class="badge-soft py-1 pr-1 text-xs"
                                    >
                                        #{{ tag }}
                                        <button
                                            type="button"
                                            :class="[
                                                'flex size-5 items-center justify-center rounded-full opacity-60 transition-opacity hover:opacity-100',
                                                focusClass,
                                            ]"
                                            :aria-label="`Remove tag ${tag}`"
                                            @click="removeTag(tag)"
                                        >
                                            <X
                                                class="size-3"
                                                aria-hidden="true"
                                            />
                                        </button>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label :class="labelClass" for="moment-notes"
                                    >Private notes</label
                                >
                                <textarea
                                    id="moment-notes"
                                    v-model="form.private_notes"
                                    rows="2"
                                    class="input-premium"
                                    placeholder="Just for you..."
                                />
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    id="favourite"
                                    v-model="form.is_favourite"
                                    type="checkbox"
                                    class="size-5 rounded accent-primary"
                                />
                                <label
                                    for="favourite"
                                    class="text-sm font-medium text-foreground"
                                    >Mark as favourite</label
                                >
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
                                <PrimaryButton
                                    type="submit"
                                    full-width
                                    :loading="form.processing"
                                >
                                    {{
                                        editingMoment
                                            ? 'Save changes'
                                            : 'Capture moment'
                                    }}
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
