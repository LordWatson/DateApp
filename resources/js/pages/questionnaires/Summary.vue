<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Bookmark, Check, LocateFixed, MapPin, Pencil, X } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface Option {
    id: number;
    title: string;
    emoji: string | null;
    value: string;
}

interface AnswerData {
    value: string | null;
    option_title: string | null;
    option_emoji: string | null;
}

interface QuestionData {
    id: number;
    title: string;
    emoji: string | null;
    type: string;
    unit: string | null;
    display_order: number;
    options: Option[];
}

interface GroupedAnswer {
    question: QuestionData;
    answers: AnswerData[];
}

interface SavedProfile {
    id: number;
    name: string;
    emoji: string | null;
    colour: string | null;
}

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
    is_solo?: boolean;
}

interface Props {
    questionnaire: QuestionnaireData;
    response_status: string;
    grouped_answers: GroupedAnswer[];
    saved_profiles: SavedProfile[];
}

const props = defineProps<Props>();

// Set by the server when `finish` is refused (required questions unanswered).
const finishError = computed(
    () => (usePage().props.errors as Record<string, string>)?.answers ?? null,
);

const showSaveModal = ref(false);
const profileName = ref('');
const profileEmoji = ref('❤️');
const profileColour = ref('#EC4899');
const saving = ref(false);
const finishing = ref(false);

type LocationStatus =
    | 'idle'
    | 'requesting'
    | 'granted'
    | 'denied'
    | 'unavailable'
    | 'manual'
    | 'searching';

const locationStatus = ref<LocationStatus>('idle');
const locationSource = ref<'geolocation' | 'manual' | null>(null);
const locationLatitude = ref<number | null>(null);
const locationLongitude = ref<number | null>(null);
const locationLabel = ref<string | null>(null);
const locationCity = ref<string | null>(null);
const locationRegion = ref<string | null>(null);
const locationCountry = ref<string | null>(null);
const locationError = ref<string | null>(null);
const manualLocationQuery = ref<string>('');
const manualLocationError = ref<string | null>(null);

const DEFAULT_TRAVEL_RADIUS_MINUTES = 60;
const travelRadiusMinutes = ref<number>(DEFAULT_TRAVEL_RADIUS_MINUTES);
const TRAVEL_RADIUS_MIN = 0;
const TRAVEL_RADIUS_MAX = 240;
const TRAVEL_RADIUS_STEP = 30;

// No guessing: when the browser cannot give a position, ask for a place.
function askForManualLocation(reason: string): void {
    locationLatitude.value = null;
    locationLongitude.value = null;
    locationLabel.value = null;
    locationCity.value = null;
    locationRegion.value = null;
    locationCountry.value = null;
    locationSource.value = null;
    locationStatus.value = 'manual';
    locationError.value = reason;
}

interface ReverseGeocodeResult {
    label: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
}

async function reverseGeocode(
    latitude: number,
    longitude: number,
): Promise<ReverseGeocodeResult> {
    try {
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.searchParams.set('format', 'jsonv2');
        url.searchParams.set('lat', String(latitude));
        url.searchParams.set('lon', String(longitude));
        url.searchParams.set('zoom', '14');
        url.searchParams.set('addressdetails', '1');

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return { label: null, city: null, region: null, country: null };
        }

        const data = (await response.json()) as {
            display_name?: string;
            address?: {
                city?: string;
                town?: string;
                village?: string;
                municipality?: string;
                suburb?: string;
                hamlet?: string;
                state?: string;
                region?: string;
                county?: string;
                country?: string;
            };
        };

        const address = data.address ?? {};
        const city =
            address.city ??
            address.town ??
            address.village ??
            address.municipality ??
            address.suburb ??
            address.hamlet ??
            null;
        const region =
            address.state ?? address.region ?? address.county ?? null;
        const country = address.country ?? null;
        const label =
            [city, region, country]
                .filter((v): v is string => !!v)
                .join(', ') ||
            data.display_name ||
            null;

        return { label, city, region, country };
    } catch {
        return { label: null, city: null, region: null, country: null };
    }
}

function requestGeolocation(): void {
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
        askForManualLocation(
            "Your browser can't share a location. Enter a town or postcode below.",
        );

        return;
    }

    locationStatus.value = 'requesting';
    locationError.value = null;

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            locationLatitude.value = position.coords.latitude;
            locationLongitude.value = position.coords.longitude;

            const result = await reverseGeocode(
                position.coords.latitude,
                position.coords.longitude,
            );
            locationLabel.value = result.label;
            locationCity.value = result.city;
            locationRegion.value = result.region;
            locationCountry.value = result.country;

            locationSource.value = 'geolocation';
            locationStatus.value = 'granted';
            locationError.value = null;
        },
        (error) => {
            const reason =
                error.code === error.PERMISSION_DENIED
                    ? 'Location permission was declined. Enter a town or postcode below.'
                    : "We couldn't read your location. Enter a town or postcode below.";

            askForManualLocation(reason);
        },
        {
            enableHighAccuracy: false,
            timeout: 10_000,
            maximumAge: 5 * 60 * 1000,
        },
    );
}

interface ForwardGeocodeResult {
    latitude: number;
    longitude: number;
    label: string;
    city: string | null;
    region: string | null;
    country: string | null;
}

async function forwardGeocode(
    query: string,
): Promise<ForwardGeocodeResult | null> {
    try {
        const url = new URL('https://nominatim.openstreetmap.org/search');
        url.searchParams.set('format', 'jsonv2');
        url.searchParams.set('q', query);
        url.searchParams.set('addressdetails', '1');
        url.searchParams.set('limit', '1');

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return null;
        }

        const data = (await response.json()) as Array<{
            lat?: string;
            lon?: string;
            display_name?: string;
            address?: {
                city?: string;
                town?: string;
                village?: string;
                municipality?: string;
                state?: string;
                region?: string;
                county?: string;
                country?: string;
            };
        }>;

        const first = data[0];

        if (!first || !first.lat || !first.lon) {
            return null;
        }

        const address = first.address ?? {};
        const city =
            address.city ??
            address.town ??
            address.village ??
            address.municipality ??
            null;
        const region =
            address.state ?? address.region ?? address.county ?? null;
        const country = address.country ?? null;
        const label =
            [city, region, country]
                .filter((v): v is string => !!v)
                .join(', ') ||
            first.display_name ||
            query;

        return {
            latitude: Number(first.lat),
            longitude: Number(first.lon),
            label,
            city,
            region,
            country,
        };
    } catch {
        return null;
    }
}

async function submitManualLocation(): Promise<void> {
    const query = manualLocationQuery.value.trim();

    if (query === '') {
        manualLocationError.value = 'Please enter a place, town or city.';

        return;
    }

    manualLocationError.value = null;
    locationStatus.value = 'searching';

    const result = await forwardGeocode(query);

    if (!result) {
        locationStatus.value = locationSource.value ? 'granted' : 'manual';
        manualLocationError.value =
            "We couldn't find that place. Try a nearby town or city.";

        return;
    }

    locationLatitude.value = result.latitude;
    locationLongitude.value = result.longitude;
    locationLabel.value = result.label;
    locationCity.value = result.city;
    locationRegion.value = result.region;
    locationCountry.value = result.country;
    locationSource.value = 'manual';
    locationStatus.value = 'granted';
    locationError.value = null;
    manualLocationQuery.value = '';
}

function openManualEntry(): void {
    manualLocationError.value = null;
    locationStatus.value = 'manual';
}

const locationCardHeadline = computed(() => {
    if (locationStatus.value === 'granted' && locationLabel.value) {
        return `Near ${locationLabel.value}`;
    }

    if (locationStatus.value === 'granted') {
        return 'Location captured';
    }

    return 'Where are you starting from?';
});

// A finished attempt is shown read-only: nothing left to edit, locate or submit.
const isCompleted = computed(() => props.response_status === 'completed');

onMounted(() => {
    if (props.questionnaire.is_solo && !isCompleted.value) {
        requestGeolocation();
    }
});

const answeredCount = computed(
    () => props.grouped_answers.filter((g) => g.answers.length > 0).length,
);

const emojiOptions = ['❤️', '🎬', '🏨', '🌧️', '🎉', '💕', '✨', '🌹'];
const colourOptions = [
    '#d6336c',
    '#6d2452',
    '#10B981',
    '#F59E0B',
    '#3B82F6',
    '#F43F5E',
];

function formatMinutes(minutes: number): string {
    if (minutes <= 0) {
        return 'Right here';
    }

    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    if (hours === 0) {
        return `${mins} min`;
    }

    if (mins === 0) {
        return hours === 1 ? '1 hour' : `${hours} hours`;
    }

    return `${hours}h ${mins}m`;
}

function formatAnswer(group: GroupedAnswer): string {
    if (group.answers.length === 0) {
        return 'Not answered';
    }

    if (group.question.type === 'slider') {
        const raw = group.answers[0]?.value ?? 'Not answered';
        const parsed = Number(raw);

        if (group.question.unit === 'minutes' && Number.isFinite(parsed)) {
            return formatMinutes(parsed);
        }

        if (group.question.unit && Number.isFinite(parsed)) {
            return `${parsed} ${group.question.unit}`;
        }

        return raw;
    }

    if (group.question.type === 'text' || group.question.type === 'textarea') {
        return group.answers[0]?.value ?? 'Not answered';
    }

    return group.answers
        .map((a) => {
            const rawValue = a.value ?? '';

            if (rawValue.startsWith('custom_amount:')) {
                const amount = rawValue.slice('custom_amount:'.length);

                return `💷 £${amount}`;
            }

            if (rawValue === 'custom_amount') {
                return '💷 Custom amount';
            }

            const label = a.option_title ?? rawValue;

            return a.option_emoji ? `${a.option_emoji} ${label}` : label;
        })
        .join(', ');
}

function editAnswer(order: number): void {
    router.visit(
        `/questionnaires/${props.questionnaire.slug}/question/${order}`,
    );
}

async function saveProfile(): Promise<void> {
    if (!profileName.value.trim()) {
        return;
    }

    saving.value = true;

    try {
        const response = await fetch('/saved-profiles', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                ),
            },
            body: JSON.stringify({
                name: profileName.value,
                emoji: profileEmoji.value,
                colour: profileColour.value,
                questionnaire_id: props.questionnaire.id,
            }),
        });

        if (!response.ok) {
            throw new Error('save failed');
        }

        showSaveModal.value = false;
        toast.success('Profile saved. You can reuse these answers next time.');
    } catch {
        toast.error('That profile could not be saved. Please try again.');
    } finally {
        saving.value = false;
    }
}

function completeQuestionnaire(): void {
    finishing.value = true;

    const payload: Record<string, string | number> = {};

    if (props.questionnaire.is_solo) {
        if (
            locationLatitude.value !== null &&
            locationLongitude.value !== null
        ) {
            payload.location_latitude = locationLatitude.value;
            payload.location_longitude = locationLongitude.value;
        }

        if (locationLabel.value) {
            payload.location_label = locationLabel.value;
        }

        if (locationCity.value) {
            payload.location_city = locationCity.value;
        }

        if (locationRegion.value) {
            payload.location_region = locationRegion.value;
        }

        if (locationCountry.value) {
            payload.location_country = locationCountry.value;
        }

        payload.travel_radius_minutes = travelRadiusMinutes.value;
    }

    router.post(`/questionnaires/${props.questionnaire.slug}/finish`, payload, {
        onFinish: () => {
            finishing.value = false;
        },
    });
}
</script>

<template>
    <Head title="Your answers" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            :eyebrow="questionnaire.title"
            title="Your answers"
            :description="
                isCompleted
                    ? 'This is what you answered.'
                    : 'Review and edit before completing.'
            "
        />

        <!-- Headline -->
        <section class="card-feature flex items-end justify-between gap-4 p-6">
            <div>
                <p class="eyebrow text-gold-soft">Answered</p>
                <p
                    class="mt-2 font-display text-5xl leading-none font-medium tabular-nums"
                >
                    {{ answeredCount }}
                    <span class="text-2xl text-white/70"
                        >/ {{ grouped_answers.length }}</span
                    >
                </p>
            </div>
            <p class="text-sm text-white/80">questions</p>
        </section>

        <!-- Answer cards -->
        <section class="space-y-3">
            <SectionHeader title="Answers" />

            <div class="divide-y divide-border card-premium px-5 py-1">
                <div
                    v-for="group in grouped_answers"
                    :key="group.question.id"
                    class="flex items-start gap-3.5 py-4"
                >
                    <span
                        v-if="group.question.emoji"
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                    >
                        {{ group.question.emoji }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-xs font-medium text-muted-foreground tabular-nums"
                        >
                            Q{{ group.question.display_order }}
                        </p>
                        <p
                            class="mt-0.5 text-sm leading-snug font-medium text-foreground"
                        >
                            {{ group.question.title }}
                        </p>
                        <p class="mt-1 text-sm leading-snug text-primary">
                            {{ formatAnswer(group) }}
                        </p>
                    </div>
                    <button
                        v-if="!isCompleted"
                        type="button"
                        class="-mr-2 inline-flex min-h-10 shrink-0 items-center rounded-full px-3 text-[13px] font-medium text-primary transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        @click="editAnswer(group.question.display_order)"
                    >
                        Edit
                    </button>
                </div>
            </div>
        </section>

        <!-- Combined location + travel radius (solo questionnaires only) -->
        <section
            v-if="questionnaire.is_solo && !isCompleted"
            class="space-y-4 card-premium p-5"
        >
            <div class="flex items-start gap-3.5">
                <span class="icon-tile h-11 w-11" aria-hidden="true">
                    <MapPin class="size-5 text-primary" />
                </span>
                <div class="min-w-0 flex-1 space-y-1">
                    <h2
                        class="text-lg leading-tight font-semibold text-foreground"
                    >
                        {{ locationCardHeadline }}
                    </h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        We'll plan around this location and stay within your
                        travel radius. Use your current location, or type in
                        somewhere you'll be visiting.
                    </p>
                </div>
            </div>

            <!-- Current location summary -->
            <div
                v-if="locationStatus === 'granted'"
                class="space-y-2 rounded-2xl bg-muted/60 p-4 text-sm"
            >
                <p class="flex items-start gap-2 font-medium text-foreground">
                    <Check
                        class="mt-0.5 size-4 shrink-0 text-success"
                        aria-hidden="true"
                    />
                    <span>
                        Planning around
                        <span class="text-primary">{{
                            locationLabel ?? 'your current spot'
                        }}</span>
                    </span>
                </p>
                <p
                    v-if="locationSource === 'manual'"
                    class="text-xs text-muted-foreground"
                >
                    Using the location you entered.
                </p>
                <div class="flex flex-wrap gap-x-4">
                    <button
                        type="button"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-full text-[13px] font-medium text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        @click="requestGeolocation"
                    >
                        <LocateFixed class="size-4" aria-hidden="true" />
                        Use my current location
                    </button>
                    <button
                        type="button"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-full text-[13px] font-medium text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        @click="openManualEntry"
                    >
                        <Pencil class="size-4" aria-hidden="true" />
                        Enter a different location
                    </button>
                </div>
            </div>

            <div
                v-else-if="locationStatus === 'requesting'"
                class="rounded-2xl bg-muted/60 p-4 text-sm text-muted-foreground"
            >
                Finding you…
            </div>

            <div
                v-else-if="locationStatus === 'searching'"
                class="rounded-2xl bg-muted/60 p-4 text-sm text-muted-foreground"
            >
                Looking up that location…
            </div>

            <div v-else class="space-y-3">
                <SecondaryButton full-width @click="requestGeolocation">
                    <LocateFixed class="size-4" aria-hidden="true" />
                    Use my current location
                </SecondaryButton>
                <SecondaryButton
                    variant="outline"
                    full-width
                    @click="openManualEntry"
                >
                    <Pencil class="size-4" aria-hidden="true" />
                    Enter a location manually
                </SecondaryButton>
                <p
                    v-if="locationError"
                    class="text-xs leading-relaxed text-muted-foreground"
                >
                    {{ locationError }}
                </p>
            </div>

            <!-- Manual entry form -->
            <div
                v-if="locationStatus === 'manual'"
                class="space-y-3 border-t border-border pt-4"
            >
                <label
                    for="manual-location"
                    class="block text-sm font-medium text-foreground"
                >
                    Enter a town, city or postcode
                </label>
                <input
                    id="manual-location"
                    v-model="manualLocationQuery"
                    type="text"
                    placeholder="e.g. Bath, United Kingdom"
                    class="input-premium"
                    @keydown.enter.prevent="submitManualLocation"
                />
                <p v-if="manualLocationError" class="text-xs text-warning">
                    {{ manualLocationError }}
                </p>
                <SecondaryButton full-width @click="submitManualLocation">
                    Use this location
                </SecondaryButton>
            </div>

            <!-- Travel radius slider -->
            <div class="space-y-3 border-t border-border pt-4">
                <div class="flex items-baseline justify-between gap-4">
                    <label
                        for="travel-radius"
                        class="text-sm font-medium text-foreground"
                    >
                        How far are you happy to drive?
                    </label>
                    <span
                        class="shrink-0 font-display text-lg text-primary tabular-nums"
                    >
                        {{ formatMinutes(travelRadiusMinutes) }}
                    </span>
                </div>
                <input
                    id="travel-radius"
                    v-model.number="travelRadiusMinutes"
                    type="range"
                    :min="TRAVEL_RADIUS_MIN"
                    :max="TRAVEL_RADIUS_MAX"
                    :step="TRAVEL_RADIUS_STEP"
                    class="w-full accent-primary"
                />
                <p class="text-xs leading-relaxed text-muted-foreground">
                    We'll suggest ideas reachable within this driving time (by
                    car) from your location.
                </p>
            </div>
        </section>

        <!-- Actions -->
        <div class="space-y-3">
            <p
                v-if="finishError && !isCompleted"
                class="text-center text-sm text-destructive"
                role="alert"
            >
                {{ finishError }}
            </p>
            <PrimaryButton
                v-if="isCompleted"
                full-width
                @click="
                    router.visit(
                        `/questionnaires/${questionnaire.slug}/complete`,
                    )
                "
            >
                See your date night
            </PrimaryButton>
            <PrimaryButton
                v-else
                full-width
                :loading="finishing"
                @click="completeQuestionnaire"
            >
                Complete questionnaire
            </PrimaryButton>

            <SecondaryButton
                variant="ghost"
                full-width
                @click="showSaveModal = true"
            >
                <Bookmark class="size-4" aria-hidden="true" />
                Save as favourite profile
            </SecondaryButton>
        </div>
    </div>

    <!-- Save profile modal -->
    <Transition name="fade">
        <div
            v-if="showSaveModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 backdrop-blur-sm sm:items-center sm:p-4"
            @click.self="showSaveModal = false"
        >
            <div
                class="w-full max-w-md space-y-5 card-premium rounded-b-none p-6 sm:rounded-3xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="save-profile-title"
            >
                <div class="flex items-center justify-between gap-4">
                    <h2
                        id="save-profile-title"
                        class="text-xl font-semibold text-foreground"
                    >
                        Save as profile
                    </h2>
                    <button
                        type="button"
                        class="-mr-2 flex size-10 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        aria-label="Close"
                        @click="showSaveModal = false"
                    >
                        <X class="size-5" aria-hidden="true" />
                    </button>
                </div>

                <div class="space-y-4">
                    <input
                        v-model="profileName"
                        type="text"
                        placeholder="Give this profile a name…"
                        aria-label="Profile name"
                        class="input-premium"
                    />

                    <div>
                        <p class="mb-2 eyebrow">Choose an emoji</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="e in emojiOptions"
                                :key="e"
                                type="button"
                                :aria-pressed="profileEmoji === e"
                                :class="[
                                    'icon-tile h-11 w-11 text-xl transition-all focus-visible:outline-none',
                                    profileEmoji === e
                                        ? 'ring-2 ring-primary'
                                        : 'focus-visible:ring-2 focus-visible:ring-primary/50',
                                ]"
                                @click="profileEmoji = e"
                            >
                                {{ e }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 eyebrow">Choose a colour</p>
                        <div class="flex gap-3">
                            <button
                                v-for="c in colourOptions"
                                :key="c"
                                type="button"
                                :aria-pressed="profileColour === c"
                                :aria-label="`Colour ${c}`"
                                :class="[
                                    'size-9 rounded-full ring-offset-2 ring-offset-card transition-all focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none',
                                    profileColour === c
                                        ? 'ring-2 ring-foreground'
                                        : '',
                                ]"
                                :style="{ backgroundColor: c }"
                                @click="profileColour = c"
                            />
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <PrimaryButton
                        full-width
                        :loading="saving"
                        @click="saveProfile"
                    >
                        Save profile
                    </PrimaryButton>
                    <SecondaryButton
                        variant="ghost"
                        full-width
                        @click="showSaveModal = false"
                        >Cancel</SecondaryButton
                    >
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
