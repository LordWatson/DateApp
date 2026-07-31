<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { destroy, index } from '@/routes/calendar';

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

const props = defineProps<{
    event: CalendarEvent;
}>();

function formatDate(dateStr: string): string {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function deleteEvent(): void {
    router.delete(destroy.url({ calendarEvent: props.event.id }));
}
</script>

<template>
    <Head :title="event.title" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <div>
            <Link
                :href="index.url()"
                class="inline-flex items-center gap-1 text-sm font-semibold text-primary transition-opacity hover:opacity-70"
            >
                ← Back to calendar
            </Link>
        </div>

        <div class="card-premium relative overflow-hidden">
            <div
                class="absolute left-0 top-0 h-full w-1 rounded-l-3xl"
                :style="{ backgroundColor: event.colour }"
                aria-hidden="true"
            />
            <div class="space-y-5 p-6 pl-7">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-3xl"
                        :style="{ backgroundColor: event.colour + '20' }"
                        aria-hidden="true"
                    >
                        {{ event.emoji ?? '📅' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-semibold text-foreground">{{ event.title }}</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ formatDate(event.date) }}{{ event.time ? ' · ' + event.time : '' }}
                        </p>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            {{ event.is_mine ? 'Added by you' : 'Added by ' + event.owner }}
                        </p>
                    </div>
                </div>

                <div v-if="event.location" class="rounded-2xl bg-background/60 p-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Location</p>
                    <p class="mt-1 text-sm text-foreground">📍 {{ event.location }}</p>
                </div>

                <div v-if="event.description" class="rounded-2xl bg-background/60 p-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Description</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-foreground">{{ event.description }}</p>
                </div>

                <div v-if="event.notes" class="rounded-2xl bg-background/60 p-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Notes</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-foreground">{{ event.notes }}</p>
                </div>

                <div v-if="event.reminder" class="rounded-2xl bg-background/60 p-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Reminder</p>
                    <p class="mt-1 text-sm text-foreground">🔔 {{ event.reminder.replace('_', ' ') }}</p>
                </div>
            </div>
        </div>

        <div v-if="event.is_mine" class="flex gap-3">
            <SecondaryButton variant="outline" full-width @click="deleteEvent">Delete</SecondaryButton>
            <PrimaryButton full-width @click="router.get(index.url())">Back</PrimaryButton>
        </div>
    </div>
</template>
