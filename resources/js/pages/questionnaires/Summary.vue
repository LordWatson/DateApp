<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
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

const showSaveModal = ref(false);
const profileName = ref('');
const profileEmoji = ref('❤️');
const profileColour = ref('#EC4899');
const saving = ref(false);
const finishing = ref(false);

type LocationStatus = 'idle' | 'requesting' | 'granted' | 'denied' | 'unavailable' | 'manual' | 'searching';

const locationStatus = ref<LocationStatus>('idle');
const locationSource = ref<'geolocation' | 'manual' | 'fallback' | null>(null);
const locationLatitude = ref<number | null>(null);
const locationLongitude = ref<number | null>(null);
const locationLabel = ref<string | null>(null);
const locationCity = ref<string | null>(null);
const locationRegion = ref<string | null>(null);
const locationCountry = ref<string | null>(null);
const locationError = ref<string | null>(null);
const manualLocationQuery = ref<string>('');
const manualLocationError = ref<string | null>(null);

// Default fallback location — used when geolocation is unavailable (e.g. HTTP
// local development) so the questionnaire can still be completed end-to-end.
const FALLBACK_LOCATION = {
    latitude: 51.4545,
    longitude: -2.5879,
    label: 'Bristol, United Kingdom',
    city: 'Bristol',
    region: 'England',
    country: 'United Kingdom',
} as const;

const DEFAULT_TRAVEL_RADIUS_MINUTES = 60;
const travelRadiusMinutes = ref<number>(DEFAULT_TRAVEL_RADIUS_MINUTES);
const TRAVEL_RADIUS_MIN = 0;
const TRAVEL_RADIUS_MAX = 240;
const TRAVEL_RADIUS_STEP = 30;

function applyFallbackLocation(reason: string | null): void {
    locationLatitude.value = FALLBACK_LOCATION.latitude;
    locationLongitude.value = FALLBACK_LOCATION.longitude;
    locationLabel.value = FALLBACK_LOCATION.label;
    locationCity.value = FALLBACK_LOCATION.city;
    locationRegion.value = FALLBACK_LOCATION.region;
    locationCountry.value = FALLBACK_LOCATION.country;
    locationSource.value = 'fallback';
    locationStatus.value = 'granted';
    locationError.value = reason;
}

interface ReverseGeocodeResult {
    label: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
}

async function reverseGeocode(latitude: number, longitude: number): Promise<ReverseGeocodeResult> {
    try {
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.searchParams.set('format', 'jsonv2');
        url.searchParams.set('lat', String(latitude));
        url.searchParams.set('lon', String(longitude));
        url.searchParams.set('zoom', '10');

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
                state?: string;
                region?: string;
                county?: string;
                country?: string;
            };
        };

        const address = data.address ?? {};
        const city = address.city ?? address.town ?? address.village ?? address.municipality ?? null;
        const region = address.state ?? address.region ?? address.county ?? null;
        const country = address.country ?? null;
        const label = [city, region, country].filter((v): v is string => !!v).join(', ') || data.display_name || null;

        return { label, city, region, country };
    } catch {
        return { label: null, city: null, region: null, country: null };
    }
}

function requestGeolocation(): void {
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
        applyFallbackLocation('Your browser doesn\'t support geolocation, so we\'ve defaulted to Bristol. You can enter a location manually below.');

        return;
    }

    locationStatus.value = 'requesting';
    locationError.value = null;

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            locationLatitude.value = position.coords.latitude;
            locationLongitude.value = position.coords.longitude;

            const result = await reverseGeocode(position.coords.latitude, position.coords.longitude);
            locationLabel.value = result.label;
            locationCity.value = result.city;
            locationRegion.value = result.region;
            locationCountry.value = result.country;

            locationSource.value = 'geolocation';
            locationStatus.value = 'granted';
            locationError.value = null;
        },
        (error) => {
            const reason = error.code === error.PERMISSION_DENIED
                ? 'Location permission was declined, so we\'ve defaulted to Bristol. You can enter a location manually below.'
                : 'We couldn\'t read your location, so we\'ve defaulted to Bristol. You can enter a location manually below.';

            applyFallbackLocation(reason);
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

async function forwardGeocode(query: string): Promise<ForwardGeocodeResult | null> {
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
        const city = address.city ?? address.town ?? address.village ?? address.municipality ?? null;
        const region = address.state ?? address.region ?? address.county ?? null;
        const country = address.country ?? null;
        const label = [city, region, country].filter((v): v is string => !!v).join(', ') || first.display_name || query;

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
        manualLocationError.value = 'We couldn\'t find that place. Try a nearby town or city.';

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
        return `📍 Near ${locationLabel.value}`;
    }

    if (locationStatus.value === 'granted') {
        return '📍 Location captured';
    }

    return '📍 Where are you starting from?';
});

onMounted(() => {
    if (props.questionnaire.is_solo) {
        requestGeolocation();
    }
});

const emojiOptions = ['❤️', '🎬', '🏨', '🌧️', '🎉', '💕', '✨', '🌹'];
const colourOptions = ['#EC4899', '#9333EA', '#10B981', '#F59E0B', '#3B82F6', '#F43F5E'];

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
    router.visit(`/questionnaires/${props.questionnaire.slug}/question/${order}`);
}

async function saveProfile(): Promise<void> {
    if (!profileName.value.trim()) {
        return;
    }

    saving.value = true;

    try {
        await fetch('/saved-profiles', {
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
        showSaveModal.value = false;
    } finally {
        saving.value = false;
    }
}

function completeQuestionnaire(): void {
    finishing.value = true;

    const payload: Record<string, string | number> = {};

    if (props.questionnaire.is_solo) {
        if (locationLatitude.value !== null && locationLongitude.value !== null) {
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
    <Head title="Your Answers" />

    <div class="space-y-4 px-4 py-6 pb-32">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-foreground">Your Answers 📋</h1>
            <p class="text-sm text-muted-foreground">Review and edit before completing.</p>
        </div>

        <!-- Answer cards -->
        <div
            v-for="group in grouped_answers"
            :key="group.question.id"
            class="card-premium p-5"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0 flex-1">
                    <span v-if="group.question.emoji" class="shrink-0 text-2xl" aria-hidden="true">
                        {{ group.question.emoji }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Q{{ group.question.display_order }}
                        </p>
                        <p class="text-sm font-semibold text-foreground">{{ group.question.title }}</p>
                        <p class="mt-1 text-sm text-primary font-medium">{{ formatAnswer(group) }}</p>
                    </div>
                </div>
                <button
                    class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-medium text-primary hover:bg-primary/10 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                    @click="editAnswer(group.question.display_order)"
                >
                    Edit
                </button>
            </div>
        </div>

        <!-- Combined location + travel radius (solo questionnaires only) -->
        <div v-if="questionnaire.is_solo" class="card-premium space-y-4 p-5">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-foreground">{{ locationCardHeadline }}</h2>
                <p class="text-sm text-muted-foreground">
                    We'll plan around this location and stay within your travel radius.
                    Use your current location, or type in somewhere you'll be visiting.
                </p>
            </div>

            <!-- Current location summary -->
            <div v-if="locationStatus === 'granted'" class="space-y-2 rounded-2xl bg-primary/5 p-4 text-sm">
                <p class="font-semibold text-foreground">
                    ✅ Planning around <span class="text-primary">{{ locationLabel ?? 'your current spot' }}</span>
                </p>
                <p v-if="locationSource === 'fallback'" class="text-xs text-muted-foreground">
                    {{ locationError ?? "We couldn't detect your location, so we've defaulted to Bristol. You can enter one manually below." }}
                </p>
                <p v-else-if="locationSource === 'manual'" class="text-xs text-muted-foreground">
                    Using the location you entered.
                </p>
                <div class="flex flex-wrap gap-3 pt-1">
                    <button
                        type="button"
                        class="text-xs font-medium text-primary underline underline-offset-2 hover:opacity-80"
                        @click="requestGeolocation"
                    >
                        📍 Use my current location
                    </button>
                    <button
                        type="button"
                        class="text-xs font-medium text-primary underline underline-offset-2 hover:opacity-80"
                        @click="openManualEntry"
                    >
                        ✏️ Enter a different location
                    </button>
                </div>
            </div>

            <div v-else-if="locationStatus === 'requesting'" class="rounded-2xl bg-muted/40 p-4 text-sm text-muted-foreground">
                Finding you…
            </div>

            <div v-else-if="locationStatus === 'searching'" class="rounded-2xl bg-muted/40 p-4 text-sm text-muted-foreground">
                Looking up that location…
            </div>

            <div v-else class="space-y-3">
                <PrimaryButton full-width @click="requestGeolocation">
                    📍 Use my current location
                </PrimaryButton>
                <SecondaryButton full-width @click="openManualEntry">
                    ✏️ Enter a location manually
                </SecondaryButton>
                <p v-if="locationError" class="text-xs text-muted-foreground">
                    {{ locationError }}
                </p>
            </div>

            <!-- Manual entry form -->
            <div v-if="locationStatus === 'manual'" class="space-y-2 rounded-2xl bg-muted/40 p-4">
                <label class="block text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Enter a town, city or postcode
                </label>
                <input
                    v-model="manualLocationQuery"
                    type="text"
                    placeholder="e.g. Bath, United Kingdom"
                    class="w-full rounded-2xl border-2 border-border bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none"
                    @keydown.enter.prevent="submitManualLocation"
                />
                <p v-if="manualLocationError" class="text-xs text-warning">
                    {{ manualLocationError }}
                </p>
                <PrimaryButton full-width @click="submitManualLocation">
                    Use this location
                </PrimaryButton>
            </div>

            <!-- Travel radius slider -->
            <div class="space-y-2 rounded-2xl bg-background/60 p-4">
                <div class="flex items-center justify-between">
                    <label for="travel-radius" class="text-sm font-semibold text-foreground">
                        🚗 How far are you happy to travel?
                    </label>
                    <span class="text-sm font-semibold text-primary">
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
                <p class="text-xs text-muted-foreground">
                    We'll suggest ideas reachable within this travel time from your location.
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-3 pt-2">
            <PrimaryButton
                full-width
                :loading="finishing"
                @click="completeQuestionnaire"
            >
                Complete Questionnaire ❤️
            </PrimaryButton>

            <SecondaryButton full-width @click="showSaveModal = true">
                💾 Save as Favourite Profile
            </SecondaryButton>
        </div>
    </div>

    <!-- Save profile modal -->
    <Transition name="fade">
        <div
            v-if="showSaveModal"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 px-4 pb-8"
            @click.self="showSaveModal = false"
        >
            <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-xl space-y-5">
                <h3 class="text-lg font-semibold text-foreground">Save as Profile</h3>

                <div class="space-y-3">
                    <input
                        v-model="profileName"
                        type="text"
                        placeholder="Give this profile a name…"
                        class="w-full rounded-2xl border-2 border-border bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none"
                    />

                    <div>
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Choose an emoji</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="e in emojiOptions"
                                :key="e"
                                :class="[
                                    'rounded-xl p-2 text-xl transition-all',
                                    profileEmoji === e ? 'bg-primary/20 ring-2 ring-primary' : 'hover:bg-muted',
                                ]"
                                @click="profileEmoji = e"
                            >
                                {{ e }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Choose a colour</p>
                        <div class="flex gap-2">
                            <button
                                v-for="c in colourOptions"
                                :key="c"
                                :class="[
                                    'h-8 w-8 rounded-full transition-all',
                                    profileColour === c ? 'ring-2 ring-offset-2 ring-foreground scale-110' : '',
                                ]"
                                :style="{ backgroundColor: c }"
                                @click="profileColour = c"
                            />
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <PrimaryButton full-width :loading="saving" @click="saveProfile">
                        Save Profile
                    </PrimaryButton>
                    <SecondaryButton full-width @click="showSaveModal = false">Cancel</SecondaryButton>
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
