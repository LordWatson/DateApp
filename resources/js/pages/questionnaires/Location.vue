<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
    is_solo: boolean;
    estimated_minutes: number | null;
}

interface Progress {
    current: number;
    total: number;
    percentage: number;
    answered_count: number;
}

interface ExistingLocation {
    label: string | null;
    city: string | null;
    region: string | null;
    country: string | null;
    latitude: number | null;
    longitude: number | null;
    travel_radius_minutes: number | null;
}

interface Props {
    questionnaire: QuestionnaireData;
    progress: Progress;
    existing_location: ExistingLocation;
}

const props = defineProps<Props>();

type LocationStatus = 'idle' | 'requesting' | 'granted' | 'denied' | 'unavailable' | 'manual' | 'searching';

const locationStatus = ref<LocationStatus>('idle');
const locationSource = ref<'geolocation' | 'manual' | 'fallback' | 'existing' | null>(null);
const locationLatitude = ref<number | null>(null);
const locationLongitude = ref<number | null>(null);
const locationLabel = ref<string | null>(null);
const locationCity = ref<string | null>(null);
const locationRegion = ref<string | null>(null);
const locationCountry = ref<string | null>(null);
const locationError = ref<string | null>(null);
const manualLocationQuery = ref<string>('');
const manualLocationError = ref<string | null>(null);
const submitting = ref(false);

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
const travelRadiusMinutes = ref<number>(props.existing_location.travel_radius_minutes ?? DEFAULT_TRAVEL_RADIUS_MINUTES);
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
        const city = address.city
            ?? address.town
            ?? address.village
            ?? address.municipality
            ?? address.suburb
            ?? address.hamlet
            ?? null;
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

const canContinue = computed(() => locationStatus.value === 'granted' && locationLatitude.value !== null && locationLongitude.value !== null);

const minutesLeft = computed(() => {
    if (!props.questionnaire.estimated_minutes) {
        return null;
    }

    return 1;
});

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

onMounted(() => {
    const existing = props.existing_location;

    if (existing.latitude !== null && existing.longitude !== null) {
        locationLatitude.value = existing.latitude;
        locationLongitude.value = existing.longitude;
        locationLabel.value = existing.label;
        locationCity.value = existing.city;
        locationRegion.value = existing.region;
        locationCountry.value = existing.country;
        locationSource.value = 'existing';
        locationStatus.value = 'granted';

        return;
    }

    requestGeolocation();
});

function goBack(): void {
    router.visit(`/questionnaires/${props.questionnaire.slug}/question/${props.progress.total - 1}`);
}

function goNext(): void {
    if (!canContinue.value || submitting.value) {
        return;
    }

    submitting.value = true;

    const payload: Record<string, string | number> = {
        travel_radius_minutes: travelRadiusMinutes.value,
    };

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

    router.post(`/questionnaires/${props.questionnaire.slug}/location`, payload, {
        onFinish: () => {
            submitting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Where are you starting from?" />

    <div class="flex min-h-screen flex-col px-4 py-6 pb-32">
        <!-- Progress header -->
        <div class="mb-6 space-y-3">
            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span class="font-medium">Almost there</span>
                <span v-if="minutesLeft">~{{ minutesLeft }} min left</span>
            </div>
            <ProgressBar :value="progress.current" :max="progress.total" />
        </div>

        <!-- Question card -->
        <div class="flex-1 space-y-6">
            <div class="card-premium space-y-4 p-6">
                <div class="flex justify-center">
                    <span class="text-6xl" aria-hidden="true">📍</span>
                </div>
                <div class="space-y-2 text-center">
                    <h2 class="text-xl font-semibold leading-snug text-foreground">{{ locationCardHeadline }}</h2>
                    <p class="text-sm text-muted-foreground">
                        We'll plan around this location and stay within your travel radius.
                        Use your current location, or type in somewhere you'll be visiting.
                    </p>
                </div>
            </div>

            <!-- Current location summary -->
            <div v-if="locationStatus === 'granted'" class="card-premium space-y-3 p-5 text-sm">
                <p class="font-semibold text-foreground">
                    ✅ Planning around <span class="text-primary">{{ locationLabel ?? 'your current spot' }}</span>
                </p>
                <p v-if="locationSource === 'fallback'" class="text-xs text-muted-foreground">
                    {{ locationError ?? "We couldn't detect your location, so we've defaulted to Bristol. You can enter one manually below." }}
                </p>
                <p v-else-if="locationSource === 'manual'" class="text-xs text-muted-foreground">
                    Using the location you entered.
                </p>
                <p v-else-if="locationSource === 'existing'" class="text-xs text-muted-foreground">
                    Using the location you set earlier — feel free to change it.
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

            <div v-else-if="locationStatus === 'requesting'" class="card-premium p-5 text-sm text-muted-foreground">
                Finding you…
            </div>

            <div v-else-if="locationStatus === 'searching'" class="card-premium p-5 text-sm text-muted-foreground">
                Looking up that location…
            </div>

            <div v-else class="card-premium space-y-3 p-5">
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
            <div v-if="locationStatus === 'manual'" class="card-premium space-y-2 p-5">
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
            <div class="card-premium space-y-2 p-5">
                <div class="flex items-center justify-between">
                    <label for="travel-radius" class="text-sm font-semibold text-foreground">
                        🚗 How far are you happy to drive?
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
                    We'll suggest ideas reachable within this driving time (by car) from your location.
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 space-y-3">
            <PrimaryButton full-width :disabled="!canContinue" :loading="submitting" @click="goNext">
                Continue
            </PrimaryButton>
            <SecondaryButton full-width @click="goBack">
                Back
            </SecondaryButton>
        </div>
    </div>
</template>
