<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { destroy, store, update } from '@/routes/calendar';

defineOptions({ layout: MobileLayout });

interface CalendarEvent {
    id: number;
    title: string;
    description: string | null;
    type: string;
    emoji: string | null;
    date: string;
    time: string | null;
    location: string | null;
    notes: string | null;
    colour: string;
    reminder: string | null;
    owner: string;
    is_mine: boolean;
}

interface EventType {
    value: string;
    label: string;
    emoji: string;
}

const props = defineProps<{
    events: CalendarEvent[];
    eventTypes: EventType[];
}>();

const showForm = ref(false);
const editingEvent = ref<CalendarEvent | null>(null);

const form = useForm({
    title: '',
    description: '',
    type: 'custom',
    emoji: '',
    date: new Date().toISOString().split('T')[0],
    time: '',
    location: '',
    notes: '',
    colour: '#EC4899',
    reminder: '',
});

const today = new Date().toISOString().split('T')[0];

const upcomingEvents = computed(() =>
    props.events
        .filter((e) => e.date >= today)
        .sort((a, b) => a.date.localeCompare(b.date)),
);

const pastEvents = computed(() =>
    props.events
        .filter((e) => e.date < today)
        .sort((a, b) => b.date.localeCompare(a.date)),
);

function openCreate(): void {
    editingEvent.value = null;
    form.reset();
    form.date = today;
    showForm.value = true;
}

function openEdit(event: CalendarEvent): void {
    editingEvent.value = event;
    form.title = event.title;
    form.description = event.description ?? '';
    form.type = event.type;
    form.emoji = event.emoji ?? '';
    form.date = event.date;
    form.time = event.time ?? '';
    form.location = event.location ?? '';
    form.notes = event.notes ?? '';
    form.colour = event.colour;
    form.reminder = event.reminder ?? '';
    showForm.value = true;
}

function submit(): void {
    if (editingEvent.value) {
        form.put(update.url({ calendarEvent: editingEvent.value.id }), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
}

function deleteEvent(event: CalendarEvent): void {
    router.delete(destroy.url({ calendarEvent: event.id }));
}

function formatDate(dateStr: string): string {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

const reminderOptions = [
    { value: '', label: 'No reminder' },
    { value: 'same_day', label: 'Same day' },
    { value: '1_day', label: '1 day before' },
    { value: '3_days', label: '3 days before' },
    { value: '1_week', label: '1 week before' },
];

const inputClass = 'w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
</script>

<template>
    <Head title="Shared Calendar" />

    <div class="space-y-4 px-4 py-6 pb-24">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">📅 Shared Calendar</h1>
                <p class="text-sm text-muted-foreground">Plan your time together</p>
            </div>
            <PrimaryButton size="sm" @click="openCreate">+ Add Event</PrimaryButton>
        </div>

        <!-- Upcoming Events -->
        <div v-if="upcomingEvents.length > 0">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Upcoming</h2>
            <div class="space-y-3">
                <div
                    v-for="event in upcomingEvents"
                    :key="event.id"
                    class="card-premium group relative overflow-hidden"
                >
                    <div
                        class="absolute left-0 top-0 h-full w-1 rounded-l-3xl"
                        :style="{ backgroundColor: event.colour }"
                        aria-hidden="true"
                    />
                    <div class="flex items-start gap-4 p-5 pl-6">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl"
                            :style="{ backgroundColor: event.colour + '20' }"
                            aria-hidden="true"
                        >
                            {{ event.emoji ?? '📅' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-foreground">{{ event.title }}</p>
                            <p class="mt-0.5 text-sm text-muted-foreground">
                                {{ formatDate(event.date) }}{{ event.time ? ' · ' + event.time : '' }}
                            </p>
                            <p v-if="event.location" class="mt-0.5 text-sm text-muted-foreground">📍 {{ event.location }}</p>
                            <p v-if="event.description" class="mt-1 text-sm text-foreground/80">{{ event.description }}</p>
                        </div>
                        <div v-if="event.is_mine" class="flex shrink-0 gap-2">
                            <button
                                class="text-sm font-semibold text-primary transition-opacity hover:opacity-70 focus:outline-none"
                                @click="openEdit(event)"
                            >
                                Edit
                            </button>
                            <button
                                class="text-sm font-semibold text-destructive transition-opacity hover:opacity-70 focus:outline-none"
                                @click="deleteEvent(event)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Past Events -->
        <div v-if="pastEvents.length > 0">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Past</h2>
            <div class="space-y-3 opacity-60">
                <div
                    v-for="event in pastEvents"
                    :key="event.id"
                    class="card-premium relative overflow-hidden"
                >
                    <div
                        class="absolute left-0 top-0 h-full w-1 rounded-l-3xl"
                        :style="{ backgroundColor: event.colour }"
                        aria-hidden="true"
                    />
                    <div class="flex items-start gap-4 p-5 pl-6">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-muted text-2xl" aria-hidden="true">
                            {{ event.emoji ?? '📅' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-foreground">{{ event.title }}</p>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ formatDate(event.date) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-if="events.length === 0"
            emoji="📅"
            title="No events yet"
            description="Start planning your time together — add your first shared event."
            action-label="Create First Event"
            @action="openCreate"
        />
    </div>

    <!-- Event Form Modal -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center"
                role="dialog"
                aria-modal="true"
                aria-label="Add calendar event"
                @click.self="showForm = false"
            >
                <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-card shadow-2xl">
                    <div class="max-h-[85vh] overflow-y-auto p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-foreground">
                                {{ editingEvent ? 'Edit Event' : 'New Event' }}
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
                                <label class="mb-1 block text-sm font-semibold text-foreground" for="event-title">Title *</label>
                                <input
                                    id="event-title"
                                    v-model="form.title"
                                    type="text"
                                    :class="inputClass"
                                    placeholder="What's the occasion?"
                                    required
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-foreground" for="event-type">Type</label>
                                <select id="event-type" v-model="form.type" :class="inputClass">
                                    <option v-for="type in eventTypes" :key="type.value" :value="type.value">
                                        {{ type.emoji }} {{ type.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-foreground" for="event-date">Date *</label>
                                    <input
                                        id="event-date"
                                        v-model="form.date"
                                        type="date"
                                        :class="inputClass"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-foreground" for="event-time">Time</label>
                                    <input
                                        id="event-time"
                                        v-model="form.time"
                                        type="time"
                                        :class="inputClass"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-foreground" for="event-location">Location</label>
                                <input
                                    id="event-location"
                                    v-model="form.location"
                                    type="text"
                                    :class="inputClass"
                                    placeholder="Where?"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-foreground" for="event-description">Description</label>
                                <textarea
                                    id="event-description"
                                    v-model="form.description"
                                    rows="3"
                                    :class="inputClass"
                                    placeholder="Any details..."
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-foreground" for="event-colour">Colour</label>
                                    <input
                                        id="event-colour"
                                        v-model="form.colour"
                                        type="color"
                                        class="h-12 w-full cursor-pointer rounded-2xl border border-border p-1"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-foreground" for="event-reminder">Reminder</label>
                                    <select id="event-reminder" v-model="form.reminder" :class="inputClass">
                                        <option v-for="opt in reminderOptions" :key="opt.value" :value="opt.value">
                                            {{ opt.label }}
                                        </option>
                                    </select>
                                </div>
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
                                    {{ editingEvent ? 'Save Changes' : 'Create Event' }}
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
