<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ChevronRight,
    MapPin,
    Pencil,
    Plus,
    Sparkles,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { destroy, show, store, update } from '@/routes/calendar';
import { show as showPlan } from '@/routes/date-night';

defineOptions({ layout: MobileLayout });

interface CalendarEvent {
    id: number;
    title: string;
    description: string | null;
    type: string;
    type_label: string;
    /** The date night plan this event was added from, when the viewer can open it. */
    plan: { id: number; theme: string; emoji: string | null } | null;
    emoji: string | null;
    date: string;
    recurrence: string | null;
    /** Where the event next falls: its own date unless it repeats. */
    next_date: string;
    days_until: number;
    occurrence: number | null;
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
    /** Today's date (YYYY-MM-DD) in the user's own timezone. */
    today: string;
}>();

const showForm = ref(false);
const editingEvent = ref<CalendarEvent | null>(null);

const form = useForm({
    title: '',
    description: '',
    type: 'custom',
    emoji: '',
    date: props.today,
    recurrence: '',
    time: '',
    location: '',
    notes: '',
    colour: '#EC4899',
    reminder: '',
});

/** The pieces of a date the card shows separately: a "12 Dec" block and the weekday. */
function dateParts(dateStr: string): {
    day: number;
    month: string;
    weekday: string;
} {
    const date = new Date(dateStr + 'T00:00:00');
    const year = dateStr.slice(0, 4);

    return {
        day: date.getDate(),
        month: date.toLocaleDateString('en-GB', { month: 'short' }),
        // The year only when it is not this one.
        weekday:
            date.toLocaleDateString('en-GB', { weekday: 'long' }) +
            (year === props.today.slice(0, 4) ? '' : ` ${year}`),
    };
}

const upcomingEvents = computed(() =>
    props.events
        .filter((e) => e.next_date >= props.today)
        .sort((a, b) => a.next_date.localeCompare(b.next_date))
        .map((e) => ({ ...e, when: dateParts(e.next_date) })),
);

// "Edit" on the event page lands here with ?edit=<id>.
onMounted(() => {
    const id = Number(new URLSearchParams(window.location.search).get('edit'));
    const event = props.events.find((e) => e.id === id && e.is_mine);

    if (event) {
        openEdit(event);
    }
});

const pastEvents = computed(() =>
    props.events
        .filter((e) => e.next_date < props.today)
        .sort((a, b) => b.next_date.localeCompare(a.next_date)),
);

function openCreate(): void {
    editingEvent.value = null;
    form.reset();
    form.date = props.today;
    showForm.value = true;
}

function openEdit(event: CalendarEvent): void {
    editingEvent.value = event;
    form.title = event.title;
    form.description = event.description ?? '';
    form.type = event.type;
    form.emoji = event.emoji ?? '';
    form.date = event.date;
    form.recurrence = event.recurrence ?? '';
    form.time = event.time ?? '';
    form.location = event.location ?? '';
    form.notes = event.notes ?? '';
    form.colour = event.colour;
    form.reminder = event.reminder ?? '';
    showForm.value = true;
}

// Anniversaries and birthdays come round every year unless the user says otherwise.
watch(
    () => form.type,
    (type) => {
        if (!editingEvent.value) {
            form.recurrence = ['anniversary', 'birthday'].includes(type)
                ? 'yearly'
                : '';
        }
    },
);

function countdown(days: number): string {
    if (days === 0) {
        return 'Today';
    }

    return days === 1 ? 'Tomorrow' : `In ${days} days`;
}

function ordinal(n: number): string {
    const tens = n % 100;
    const suffix =
        tens >= 11 && tens <= 13
            ? 'th'
            : (['th', 'st', 'nd', 'rd'][n % 10] ?? 'th');

    return `${n}${suffix}`;
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

const eventToDelete = ref<CalendarEvent | null>(null);

function deleteEvent(): void {
    if (eventToDelete.value) {
        router.delete(destroy.url({ calendarEvent: eventToDelete.value.id }));
    }
}

function openShow(event: CalendarEvent): void {
    router.get(show.url({ calendarEvent: event.id }));
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

const labelClass = 'mb-1.5 block text-[13px] font-medium text-foreground/80';
const focusClass =
    'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';
const iconButtonClass =
    'flex size-10 items-center justify-center rounded-full transition-colors';
</script>

<template>
    <Head title="Shared calendar" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Together"
            title="Shared calendar"
            description="Plan your time together"
        >
            <PrimaryButton size="sm" @click="openCreate">
                <Plus class="size-4" aria-hidden="true" />
                Add event
            </PrimaryButton>
        </PageHeader>

        <!-- Upcoming events -->
        <section v-if="upcomingEvents.length > 0" class="space-y-3">
            <SectionHeader title="Upcoming" />
            <div class="space-y-3">
                <div
                    v-for="event in upcomingEvents"
                    :key="event.id"
                    role="button"
                    tabindex="0"
                    @keydown.enter.self="openShow(event)"
                    :class="[
                        'w-full card-hover cursor-pointer overflow-hidden card-premium text-left',
                        focusClass,
                    ]"
                    @click="openShow(event)"
                >
                    <div class="flex items-start gap-3.5 p-4">
                        <!-- Date block, ringed in the event's colour -->
                        <span
                            class="icon-tile h-14 w-12 flex-col gap-0.5"
                            :style="{
                                boxShadow: `inset 0 0 0 1.5px ${event.colour}`,
                            }"
                            aria-hidden="true"
                        >
                            <span
                                class="text-[10px] leading-none font-semibold tracking-wider text-gold uppercase"
                                >{{ event.when.month }}</span
                            >
                            <span
                                class="font-display text-xl leading-none font-semibold text-foreground tabular-nums"
                                >{{ event.when.day }}</span
                            >
                        </span>
                        <span class="block min-w-0 flex-1">
                            <span
                                class="flex items-center gap-1.5 font-medium text-foreground"
                            >
                                <span aria-hidden="true">{{
                                    event.emoji ?? '📅'
                                }}</span>
                                <span class="truncate">{{ event.title }}</span>
                            </span>
                            <span class="sr-only">{{
                                formatDate(event.next_date)
                            }}</span>
                            <span
                                class="mt-0.5 block text-xs text-muted-foreground"
                            >
                                {{ event.when.weekday
                                }}{{
                                    event.time
                                        ? ' · ' + event.time.slice(0, 5)
                                        : ''
                                }}{{
                                    event.is_mine
                                        ? ''
                                        : ' · Added by ' + event.owner
                                }}
                            </span>
                            <span
                                v-if="event.location"
                                class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <MapPin
                                    class="size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
                                <span class="truncate">{{
                                    event.location
                                }}</span>
                            </span>
                            <span class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    class="badge-soft bg-primary/10 text-primary tabular-nums"
                                    >{{ countdown(event.days_until) }}</span
                                >
                                <span class="badge-soft">{{
                                    event.type_label
                                }}</span>
                                <span
                                    v-if="event.recurrence"
                                    class="badge-soft"
                                >
                                    {{
                                        event.occurrence
                                            ? `${ordinal(event.occurrence)} · every year`
                                            : 'Every year'
                                    }}
                                </span>
                            </span>
                        </span>
                        <span
                            v-if="event.is_mine"
                            class="-mt-1 -mr-1.5 flex shrink-0"
                        >
                            <button
                                type="button"
                                :class="[
                                    iconButtonClass,
                                    focusClass,
                                    'text-muted-foreground hover:bg-muted hover:text-foreground',
                                ]"
                                :aria-label="`Edit ${event.title}`"
                                @click.stop="openEdit(event)"
                            >
                                <Pencil class="size-4" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                :class="[
                                    iconButtonClass,
                                    focusClass,
                                    'text-destructive hover:bg-destructive/10',
                                ]"
                                :aria-label="`Delete ${event.title}`"
                                @click.stop="eventToDelete = event"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                            </button>
                        </span>
                    </div>
                    <Link
                        v-if="event.plan"
                        :href="showPlan.url({ dateNightPlan: event.plan.id })"
                        class="flex min-h-11 items-center gap-2 border-t border-border px-4 text-sm font-medium text-primary transition-colors hover:bg-muted focus-visible:bg-muted focus-visible:outline-none"
                        @click.stop
                        @keydown.enter.stop
                    >
                        <Sparkles class="size-4 shrink-0" aria-hidden="true" />
                        View the date night plan
                        <ChevronRight
                            class="ml-auto size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Past events -->
        <section v-if="pastEvents.length > 0" class="space-y-3">
            <SectionHeader title="Past" />
            <div class="card-premium p-2">
                <button
                    v-for="event in pastEvents"
                    :key="event.id"
                    type="button"
                    :class="['list-row w-full text-left', focusClass]"
                    @click="openShow(event)"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl opacity-70"
                        aria-hidden="true"
                        >{{ event.emoji ?? '📅' }}</span
                    >
                    <span class="block min-w-0 flex-1">
                        <span
                            class="block truncate font-medium text-foreground/80"
                            >{{ event.title }}</span
                        >
                        <span class="block text-xs text-muted-foreground">{{
                            formatDate(event.date)
                        }}</span>
                    </span>
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </section>

        <!-- Empty state -->
        <div v-if="events.length === 0" class="card-premium">
            <EmptyState
                emoji="📅"
                title="No events yet"
                description="Start planning your time together — add your first shared event."
                action-label="Create first event"
                @action="openCreate"
            />
        </div>
    </div>

    <!-- Event form modal -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="showForm"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-label="Add calendar event"
                @click.self="showForm = false"
            >
                <div
                    class="w-full max-w-lg overflow-hidden card-premium rounded-b-none sm:rounded-3xl"
                >
                    <div class="max-h-[85vh] overflow-y-auto p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-foreground">
                                {{ editingEvent ? 'Edit event' : 'New event' }}
                            </h2>
                            <button
                                type="button"
                                :class="[
                                    iconButtonClass,
                                    focusClass,
                                    '-mr-2 text-muted-foreground hover:bg-muted hover:text-foreground',
                                ]"
                                aria-label="Close"
                                @click="showForm = false"
                            >
                                <X class="size-5" aria-hidden="true" />
                            </button>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <label :class="labelClass" for="event-title"
                                    >Title *</label
                                >
                                <input
                                    id="event-title"
                                    v-model="form.title"
                                    type="text"
                                    class="input-premium"
                                    placeholder="What's the occasion?"
                                    required
                                />
                                <p
                                    v-if="form.errors.title"
                                    class="mt-1 text-sm text-destructive"
                                >
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div>
                                <label :class="labelClass" for="event-type"
                                    >Type</label
                                >
                                <select
                                    id="event-type"
                                    v-model="form.type"
                                    class="input-premium"
                                >
                                    <option
                                        v-for="type in eventTypes"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.emoji }} {{ type.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label :class="labelClass" for="event-date"
                                        >Date *</label
                                    >
                                    <input
                                        id="event-date"
                                        v-model="form.date"
                                        type="date"
                                        class="input-premium"
                                        required
                                    />
                                </div>
                                <div>
                                    <label :class="labelClass" for="event-time"
                                        >Time</label
                                    >
                                    <input
                                        id="event-time"
                                        v-model="form.time"
                                        type="time"
                                        class="input-premium"
                                    />
                                </div>
                            </div>
                            <p
                                v-if="form.errors.date"
                                class="-mt-3 text-sm text-destructive"
                            >
                                {{ form.errors.date }}
                            </p>

                            <div class="flex items-center gap-3">
                                <input
                                    id="event-recurrence"
                                    v-model="form.recurrence"
                                    type="checkbox"
                                    true-value="yearly"
                                    false-value=""
                                    class="size-5 rounded accent-primary"
                                />
                                <label
                                    for="event-recurrence"
                                    class="text-sm font-medium text-foreground"
                                    >Repeats every year</label
                                >
                            </div>

                            <div>
                                <label :class="labelClass" for="event-location"
                                    >Location</label
                                >
                                <input
                                    id="event-location"
                                    v-model="form.location"
                                    type="text"
                                    class="input-premium"
                                    placeholder="Where?"
                                />
                            </div>

                            <div>
                                <label
                                    :class="labelClass"
                                    for="event-description"
                                    >Description</label
                                >
                                <textarea
                                    id="event-description"
                                    v-model="form.description"
                                    rows="3"
                                    class="input-premium"
                                    placeholder="Any details..."
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        :class="labelClass"
                                        for="event-colour"
                                        >Colour</label
                                    >
                                    <input
                                        id="event-colour"
                                        v-model="form.colour"
                                        type="color"
                                        class="h-[50px] w-full cursor-pointer rounded-xl border border-input bg-card p-1"
                                    />
                                </div>
                                <div>
                                    <label
                                        :class="labelClass"
                                        for="event-reminder"
                                        >Reminder</label
                                    >
                                    <select
                                        id="event-reminder"
                                        v-model="form.reminder"
                                        class="input-premium"
                                    >
                                        <option
                                            v-for="opt in reminderOptions"
                                            :key="opt.value"
                                            :value="opt.value"
                                        >
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
                                <PrimaryButton
                                    type="submit"
                                    full-width
                                    :loading="form.processing"
                                >
                                    {{
                                        editingEvent
                                            ? 'Save changes'
                                            : 'Create event'
                                    }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
    <ConfirmDialog
        :open="eventToDelete !== null"
        title="Delete this event?"
        description="It is removed from your shared calendar and cannot be brought back."
        @update:open="eventToDelete = null"
        @confirm="deleteEvent"
    />
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
