<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Bell, MapPin, Trash2 } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
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

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            :eyebrow="
                event.is_mine ? 'Added by you' : 'Added by ' + event.owner
            "
            :title="event.title"
            :description="
                formatDate(event.date) + (event.time ? ' · ' + event.time : '')
            "
            :back-href="index.url()"
            back-label="Back to calendar"
        >
            <span
                class="icon-tile h-14 w-14 text-3xl"
                :style="{ boxShadow: `inset 0 0 0 1.5px ${event.colour}` }"
                aria-hidden="true"
                >{{ event.emoji ?? '📅' }}</span
            >
        </PageHeader>

        <div
            v-if="
                event.location ||
                event.description ||
                event.notes ||
                event.reminder
            "
            class="divide-y divide-border card-premium px-6"
        >
            <div v-if="event.location" class="space-y-1.5 py-5">
                <p class="eyebrow">Location</p>
                <p class="flex items-center gap-2 text-[15px] text-foreground">
                    <MapPin
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    {{ event.location }}
                </p>
            </div>

            <div v-if="event.description" class="space-y-1.5 py-5">
                <p class="eyebrow">Description</p>
                <p
                    class="text-[15px] leading-relaxed whitespace-pre-wrap text-foreground"
                >
                    {{ event.description }}
                </p>
            </div>

            <div v-if="event.notes" class="space-y-1.5 py-5">
                <p class="eyebrow">Notes</p>
                <p
                    class="text-[15px] leading-relaxed whitespace-pre-wrap text-foreground"
                >
                    {{ event.notes }}
                </p>
            </div>

            <div v-if="event.reminder" class="space-y-1.5 py-5">
                <p class="eyebrow">Reminder</p>
                <p class="flex items-center gap-2 text-[15px] text-foreground">
                    <Bell
                        class="size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    {{ event.reminder.replace('_', ' ') }}
                </p>
            </div>
        </div>

        <div v-if="event.is_mine" class="space-y-3">
            <SecondaryButton
                variant="outline"
                full-width
                @click="router.get(index.url())"
            >
                Back
            </SecondaryButton>
            <SecondaryButton
                variant="ghost"
                full-width
                class="text-destructive! hover:bg-destructive/10!"
                @click="deleteEvent"
            >
                <Trash2 class="size-4" aria-hidden="true" />
                Delete
            </SecondaryButton>
        </div>
    </div>
</template>
