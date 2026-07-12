<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import { store, update, destroy } from '@/routes/calendar';

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
const viewMode = ref<'month' | 'agenda'>('agenda');

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

const upcomingEvents = computed(() =>
    props.events
        .filter((e) => e.date >= new Date().toISOString().split('T')[0])
        .sort((a, b) => a.date.localeCompare(b.date)),
);

const pastEvents = computed(() =>
    props.events
        .filter((e) => e.date < new Date().toISOString().split('T')[0])
        .sort((a, b) => b.date.localeCompare(a.date)),
);

function openCreate() {
    editingEvent.value = null;
    form.reset();
    form.date = new Date().toISOString().split('T')[0];
    showForm.value = true;
}

function openEdit(event: CalendarEvent) {
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

function submit() {
    if (editingEvent.value) {
        form.put(update.url({ calendarEvent: editingEvent.value.id }), {
            onSuccess: () => {
 showForm.value = false; form.reset();
},
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => {
 showForm.value = false; form.reset();
},
        });
    }
}

function deleteEvent(event: CalendarEvent) {
    if (confirm('Delete this event?')) {
        router.delete(destroy.url({ calendarEvent: event.id }));
    }
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
</script>

<template>
        <Head title="Shared Calendar" />

        <div class="min-h-screen bg-[#FFF7FB] px-4 py-8">
            <div class="mx-auto max-w-lg">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">📅 Shared Calendar</h1>
                        <p class="text-sm text-gray-500">Plan your time together</p>
                    </div>
                    <PrimaryButton @click="openCreate">+ Add Event</PrimaryButton>
                </div>

                <!-- View Toggle -->
                <div class="mb-6 flex gap-2 rounded-2xl bg-white p-1 shadow-md">
                    <button
                        v-for="mode in ['agenda', 'month']"
                        :key="mode"
                        class="flex-1 rounded-xl py-2 text-sm font-semibold capitalize transition-all"
                        :class="viewMode === mode ? 'bg-pink-500 text-white shadow' : 'text-gray-500'"
                        @click="viewMode = mode as 'month' | 'agenda'"
                    >
                        {{ mode }}
                    </button>
                </div>

                <!-- Upcoming Events -->
                <div v-if="upcomingEvents.length > 0" class="mb-8">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Upcoming</h2>
                    <div class="space-y-3">
                        <div
                            v-for="event in upcomingEvents"
                            :key="event.id"
                            class="group relative overflow-hidden rounded-3xl bg-white p-5 shadow-xl transition-all hover:shadow-2xl"
                        >
                            <div
                                class="absolute left-0 top-0 h-full w-1 rounded-l-3xl"
                                :style="{ backgroundColor: event.colour }"
                            />
                            <div class="flex items-start gap-4 pl-3">
                                <div
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl text-2xl"
                                    :style="{ backgroundColor: event.colour + '20' }"
                                >
                                    {{ event.emoji ?? '📅' }}
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">{{ event.title }}</div>
                                    <div class="mt-0.5 text-sm text-gray-500">{{ formatDate(event.date) }}{{ event.time ? ' · ' + event.time : '' }}</div>
                                    <div v-if="event.location" class="mt-0.5 text-sm text-gray-400">📍 {{ event.location }}</div>
                                    <div v-if="event.description" class="mt-1 text-sm text-gray-600">{{ event.description }}</div>
                                </div>
                                <div v-if="event.is_mine" class="flex gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button class="text-sm text-blue-500" @click="openEdit(event)">Edit</button>
                                    <button class="text-sm text-red-400" @click="deleteEvent(event)">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Past Events -->
                <div v-if="pastEvents.length > 0">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Past</h2>
                    <div class="space-y-3 opacity-60">
                        <div
                            v-for="event in pastEvents"
                            :key="event.id"
                            class="relative overflow-hidden rounded-3xl bg-white p-5 shadow-md"
                        >
                            <div
                                class="absolute left-0 top-0 h-full w-1 rounded-l-3xl"
                                :style="{ backgroundColor: event.colour }"
                            />
                            <div class="flex items-start gap-4 pl-3">
                                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-2xl">
                                    {{ event.emoji ?? '📅' }}
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-700">{{ event.title }}</div>
                                    <div class="mt-0.5 text-sm text-gray-400">{{ formatDate(event.date) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="events.length === 0" class="py-16 text-center">
                    <div class="mb-4 text-6xl">📅</div>
                    <h3 class="text-xl font-semibold text-gray-900">No events yet</h3>
                    <p class="mt-2 text-gray-500">Start planning your time together</p>
                    <PrimaryButton class="mt-6" @click="openCreate">Create First Event</PrimaryButton>
                </div>
            </div>
        </div>

        <!-- Event Form Modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
                <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <div class="max-h-[85vh] overflow-y-auto p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">
                                {{ editingEvent ? 'Edit Event' : 'New Event' }}
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
                                    placeholder="What's the occasion?"
                                    required
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Type</label>
                                <select v-model="form.type" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none">
                                    <option v-for="type in eventTypes" :key="type.value" :value="type.value">
                                        {{ type.emoji }} {{ type.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
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
                                    <label class="mb-1 block text-sm font-semibold text-gray-700">Time</label>
                                    <input
                                        v-model="form.time"
                                        type="time"
                                        class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Location</label>
                                <input
                                    v-model="form.location"
                                    type="text"
                                    class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none"
                                    placeholder="Where?"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none"
                                    placeholder="Any details..."
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-gray-700">Colour</label>
                                    <input v-model="form.colour" type="color" class="h-12 w-full cursor-pointer rounded-2xl border border-gray-200 p-1" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-gray-700">Reminder</label>
                                    <select v-model="form.reminder" class="w-full rounded-2xl border border-gray-200 px-4 py-3 focus:border-pink-400 focus:outline-none">
                                        <option v-for="opt in reminderOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                </div>
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
                                    {{ editingEvent ? 'Save Changes' : 'Create Event' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
</template>
