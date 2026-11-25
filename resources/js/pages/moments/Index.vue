<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ImagePlus, Moon, Pencil, Plus, Star, Trash2, X } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PhotoCarousel from '@/components/PhotoCarousel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import momentRoutes from '@/routes/moments';
import { store, update, show } from '@/routes/moments';

defineOptions({ layout: MobileLayout });

interface Moment {
    id: number;
    title: string;
    description: string | null;
    mood: string | null;
    /** Up to three photo URLs; the first is the cover. */
    photos: string[];
    date: string;
    is_favourite: boolean;
    private_notes: string | null;
    tags: string[];
    date_night_plan: { id: number; theme: string } | null;
    is_owner: boolean;
}

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    moments: Moment[];
    pagination: Pagination;
    /** Today's date (YYYY-MM-DD) in the user's own timezone. */
    today: string;
    /** Set by `?plan=`: capture a moment from that date night. */
    prefill: { date_night_plan_id: number; title: string; date: string } | null;
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
    date: props.today,
    is_favourite: false,
    private_notes: '',
    tags: [] as string[],
    /** Files picked in this form, added after the saved photos. */
    photos: [] as File[],
    /** Positions of saved photos to delete (edit only). */
    remove_photos: [] as number[],
    date_night_plan_id: null as number | null,
});

const MAX_PHOTOS = 3;

// The saved photos still kept (editing), and a preview for each picked file.
const savedPhotos = ref<{ url: string; index: number }[]>([]);
const newPreviews = ref<string[]>([]);
const photoCount = computed(
    () => savedPhotos.value.length + form.photos.length,
);
// Laravel reports a bad file as `photos.0`, the limit as `photos`.
const photoError = computed(
    () =>
        Object.entries(form.errors as Record<string, string>).find(([key]) =>
            key.startsWith('photos'),
        )?.[1],
);

function clearPhotoErrors(): void {
    (form.clearErrors as (...fields: string[]) => void)(
        ...Object.keys(form.errors).filter((key) => key.startsWith('photos')),
    );
}

function onPhotosChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []).slice(
        0,
        MAX_PHOTOS - photoCount.value,
    );

    form.photos.push(...files);
    newPreviews.value.push(...files.map((file) => URL.createObjectURL(file)));
    clearPhotoErrors();
    // So picking the same file again still fires `change`.
    input.value = '';
}

function removeSavedPhoto(position: number): void {
    form.remove_photos.push(savedPhotos.value[position].index);
    savedPhotos.value.splice(position, 1);
}

function removeNewPhoto(position: number): void {
    URL.revokeObjectURL(newPreviews.value[position]);
    newPreviews.value.splice(position, 1);
    form.photos.splice(position, 1);
    clearPhotoErrors();
}

function resetPhotos(saved: string[] = []): void {
    newPreviews.value.forEach((url) => URL.revokeObjectURL(url));
    newPreviews.value = [];
    form.photos = [];
    form.remove_photos = [];
    savedPhotos.value = saved.map((url, index) => ({ url, index }));
}

function openCreate(): void {
    editingMoment.value = null;
    form.reset();
    form.clearErrors();
    form.date = props.today;
    tagInput.value = '';
    resetPhotos();
    showForm.value = true;
}

function openEdit(moment: Moment): void {
    editingMoment.value = moment;
    form.clearErrors();
    resetPhotos(moment.photos);
    form.title = moment.title;
    form.description = moment.description ?? '';
    form.mood = moment.mood ?? '';
    form.date = moment.date;
    form.is_favourite = moment.is_favourite;
    form.private_notes = moment.private_notes ?? '';
    form.tags = [...moment.tags];
    // Sent on every save, so an edit must carry the link it already has.
    form.date_night_plan_id = moment.date_night_plan?.id ?? null;
    showForm.value = true;
}

onMounted(() => {
    if (props.prefill) {
        openCreate();
        form.title = props.prefill.title;
        form.date = props.prefill.date;
        form.date_night_plan_id = props.prefill.date_night_plan_id;

        return;
    }

    // "Edit" on the moment page lands here with ?edit=<id>.
    const id = Number(new URLSearchParams(window.location.search).get('edit'));
    const moment = props.moments.find((m) => m.id === id && m.is_owner);

    if (moment) {
        openEdit(moment);
    }
});

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
        // A PUT body cannot carry a file, so edits go as POST with a spoofed method.
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            update(editingMoment.value.id).url,
            {
                onSuccess: () => {
                    showForm.value = false;
                },
            },
        );
    } else {
        form.transform((data) => data).post(store().url, {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
}

const momentToDelete = ref<Moment | null>(null);

function deleteMoment(): void {
    if (momentToDelete.value) {
        router.delete(momentRoutes.destroy(momentToDelete.value.id).url);
    }
}

function toggleFavourite(moment: Moment): void {
    router.post(momentRoutes.toggleFavourite(moment.id).url);
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
                <!-- Photos: whole, never cropped; swipe for the next -->
                <PhotoCarousel
                    v-if="moment.photos.length > 0"
                    :photos="moment.photos"
                    :alt="moment.title"
                />

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
                            v-if="moment.is_owner"
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
                        class="line-clamp-3 text-sm leading-relaxed text-foreground/80"
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

                    <!-- Actions: only the person who wrote it can change it -->
                    <div
                        v-if="moment.is_owner"
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
                            @click.stop="momentToDelete = moment"
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

        <nav
            v-if="pagination.last_page > 1"
            class="flex items-center justify-between gap-3"
            aria-label="Pages of moments"
        >
            <Link
                v-if="pagination.current_page > 1"
                :href="`/moments?page=${pagination.current_page - 1}`"
                class="chip"
                >Newer</Link
            >
            <span v-else />
            <span class="text-xs text-muted-foreground tabular-nums"
                >Page {{ pagination.current_page }} of
                {{ pagination.last_page }}</span
            >
            <Link
                v-if="pagination.current_page < pagination.last_page"
                :href="`/moments?page=${pagination.current_page + 1}`"
                class="chip"
                >Older</Link
            >
            <span v-else />
        </nav>

        <ConfirmDialog
            :open="momentToDelete !== null"
            title="Delete this moment?"
            description="It is removed for both of you and cannot be brought back."
            @update:open="momentToDelete = null"
            @confirm="deleteMoment"
        />
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
                            <p
                                v-if="
                                    !editingMoment &&
                                    prefill &&
                                    form.date_night_plan_id
                                "
                                class="flex items-center gap-1.5 text-xs text-muted-foreground"
                            >
                                <Moon
                                    class="size-3.5 text-gold"
                                    aria-hidden="true"
                                />
                                From your date night: {{ prefill.title }}
                            </p>
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

                            <div role="group" aria-labelledby="moment-photos">
                                <p id="moment-photos" :class="labelClass">
                                    Photos
                                    <span
                                        class="font-normal text-muted-foreground tabular-nums"
                                        >{{ photoCount }} of
                                        {{ MAX_PHOTOS }}</span
                                    >
                                </p>
                                <div class="grid grid-cols-3 gap-2">
                                    <div
                                        v-for="(photo, i) in [
                                            ...savedPhotos.map((p) => p.url),
                                            ...newPreviews,
                                        ]"
                                        :key="photo"
                                        class="relative aspect-square overflow-hidden rounded-2xl border border-border bg-muted"
                                    >
                                        <!-- Thumbnails show the whole picture too -->
                                        <img
                                            :src="photo"
                                            :alt="`Photo ${i + 1}`"
                                            class="size-full object-contain"
                                        />
                                        <span
                                            v-if="i === 0"
                                            class="absolute bottom-1.5 left-1.5 badge-soft"
                                            >Cover</span
                                        >
                                        <button
                                            type="button"
                                            class="absolute top-1 right-1 flex size-8 items-center justify-center rounded-full bg-black/55 text-white transition-colors hover:bg-black/75 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                                            :aria-label="`Remove photo ${i + 1}`"
                                            @click="
                                                i < savedPhotos.length
                                                    ? removeSavedPhoto(i)
                                                    : removeNewPhoto(
                                                          i -
                                                              savedPhotos.length,
                                                      )
                                            "
                                        >
                                            <X
                                                class="size-4"
                                                aria-hidden="true"
                                            />
                                        </button>
                                    </div>
                                    <label
                                        v-if="photoCount < MAX_PHOTOS"
                                        class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border border-dashed border-input text-xs font-medium text-muted-foreground transition-colors focus-within:ring-2 focus-within:ring-primary hover:bg-muted hover:text-foreground"
                                    >
                                        <ImagePlus
                                            class="size-5"
                                            aria-hidden="true"
                                        />
                                        Add photo
                                        <input
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            class="sr-only"
                                            :aria-describedby="
                                                photoError
                                                    ? 'moment-photo-error'
                                                    : 'moment-photo-hint'
                                            "
                                            @change="onPhotosChange"
                                        />
                                    </label>
                                </div>
                                <p
                                    v-if="photoError"
                                    id="moment-photo-error"
                                    class="mt-1.5 text-sm text-destructive"
                                >
                                    {{ photoError }}
                                </p>
                                <p
                                    v-else
                                    id="moment-photo-hint"
                                    class="mt-1.5 text-xs text-muted-foreground"
                                >
                                    Up to {{ MAX_PHOTOS }} images, 5 MB each.
                                    The first one is the cover.
                                </p>
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
