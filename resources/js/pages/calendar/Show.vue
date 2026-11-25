<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Bell,
    ChevronRight,
    ExternalLink,
    FileText,
    MapPin,
    Pencil,
    Repeat,
    StickyNote,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { destroy, index } from '@/routes/calendar';
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

const props = defineProps<{
    event: CalendarEvent;
}>();

const reminderLabels: Record<string, string> = {
    same_day: 'On the day',
    '1_day': '1 day before',
    '3_days': '3 days before',
    '1_week': '1 week before',
};

const when = computed(() => {
    const date = new Date(props.event.next_date + 'T00:00:00');
    const days = props.event.days_until;

    return {
        day: date.toLocaleDateString('en-GB', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        }),
        // The time column can come back as HH:MM:SS.
        rest: [date.getFullYear(), props.event.time?.slice(0, 5)]
            .filter(Boolean)
            .join(' · '),
        countdown:
            days < 0
                ? 'Past event'
                : days === 0
                  ? 'Today'
                  : days === 1
                    ? 'Tomorrow'
                    : `In ${days} days`,
    };
});

const details = computed(() =>
    [
        { icon: FileText, label: 'About', value: props.event.description },
        { icon: StickyNote, label: 'Notes', value: props.event.notes },
        {
            icon: Bell,
            label: 'Reminder',
            value: props.event.reminder
                ? (reminderLabels[props.event.reminder] ?? props.event.reminder)
                : null,
        },
        {
            icon: UserRound,
            label: 'Added by',
            value: props.event.is_mine ? 'You' : props.event.owner,
        },
    ].filter((detail) => detail.value),
);

const mapsUrl = computed(
    () =>
        'https://www.google.com/maps/search/?api=1&query=' +
        encodeURIComponent(props.event.location ?? ''),
);

const focusClass =
    'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';

const confirmingDelete = ref(false);

function deleteEvent(): void {
    router.delete(destroy.url({ calendarEvent: props.event.id }));
}
</script>

<template>
    <Head :title="event.title" />

    <div class="space-y-6 px-4 pt-6 pb-28">
        <PageHeader
            :eyebrow="event.type_label"
            :title="event.title"
            :back-href="index.url()"
            back-label="Back to calendar"
        />

        <!-- When -->
        <section class="card-feature p-6">
            <span
                class="absolute inset-x-0 top-0 h-1"
                :style="{ backgroundColor: event.colour }"
                aria-hidden="true"
            />
            <div class="flex items-center gap-4">
                <span
                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-3xl ring-1 ring-white/20"
                    aria-hidden="true"
                    >{{ event.emoji ?? '📅' }}</span
                >
                <div class="min-w-0 space-y-1">
                    <p class="eyebrow text-gold-soft">{{ when.countdown }}</p>
                    <p
                        class="font-display text-2xl leading-tight font-semibold"
                    >
                        {{ when.day }}
                    </p>
                    <p class="text-sm text-white/80 tabular-nums">
                        {{ when.rest }}
                    </p>
                </div>
            </div>
            <p
                v-if="event.recurrence"
                class="mt-5 flex items-center gap-2 border-t border-white/15 pt-4 text-sm text-white/80"
            >
                <Repeat class="size-4 shrink-0" aria-hidden="true" />
                Every year since {{ event.date.slice(0, 4) }}
            </p>
        </section>

        <!-- The plan this event came from -->
        <Link
            v-if="event.plan"
            :href="showPlan.url({ dateNightPlan: event.plan.id })"
            :class="[
                'flex card-hover items-center gap-3.5 card-premium p-4',
                focusClass,
            ]"
        >
            <span class="icon-tile h-12 w-12 text-2xl" aria-hidden="true">{{
                event.plan.emoji ?? '💕'
            }}</span>
            <span class="block min-w-0 flex-1">
                <span class="block eyebrow">Date night plan</span>
                <span class="block truncate font-medium text-foreground">{{
                    event.plan.theme
                }}</span>
                <span class="block text-xs text-muted-foreground"
                    >See the meal, activity and conversation ideas</span
                >
            </span>
            <ChevronRight
                class="size-4 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
        </Link>

        <!-- Details -->
        <div class="divide-y divide-border card-premium px-5">
            <div v-if="event.location" class="flex items-start gap-3.5 py-4">
                <span
                    class="icon-tile h-10 w-10 text-muted-foreground"
                    aria-hidden="true"
                >
                    <MapPin class="size-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-muted-foreground">Where</p>
                    <p class="text-[15px] text-foreground">
                        {{ event.location }}
                    </p>
                    <a
                        :href="mapsUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="[
                            'mt-1 inline-flex min-h-9 items-center gap-1.5 rounded-full text-sm font-medium text-primary hover:underline',
                            focusClass,
                        ]"
                    >
                        Open in maps
                        <ExternalLink class="size-3.5" aria-hidden="true" />
                    </a>
                </div>
            </div>

            <div
                v-for="detail in details"
                :key="detail.label"
                class="flex items-start gap-3.5 py-4"
            >
                <span
                    class="icon-tile h-10 w-10 text-muted-foreground"
                    aria-hidden="true"
                >
                    <component :is="detail.icon" class="size-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-muted-foreground">
                        {{ detail.label }}
                    </p>
                    <p
                        class="text-[15px] leading-relaxed whitespace-pre-wrap text-foreground"
                    >
                        {{ detail.value }}
                    </p>
                </div>
            </div>
        </div>

        <div v-if="event.is_mine" class="space-y-3">
            <SecondaryButton
                variant="outline"
                full-width
                @click="router.get(index.url({ query: { edit: event.id } }))"
            >
                <Pencil class="size-4" aria-hidden="true" />
                Edit
            </SecondaryButton>
            <SecondaryButton
                variant="ghost"
                full-width
                class="text-destructive! hover:bg-destructive/10!"
                @click="confirmingDelete = true"
            >
                <Trash2 class="size-4" aria-hidden="true" />
                Delete
            </SecondaryButton>
        </div>
        <ConfirmDialog
            v-model:open="confirmingDelete"
            title="Delete this event?"
            description="It is removed from your shared calendar and cannot be brought back."
            @confirm="deleteEvent"
        />
    </div>
</template>
