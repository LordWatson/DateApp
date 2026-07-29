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

type LocationStatus = 'idle' | 'requesting' | 'granted' | 'denied' | 'unavailable';

const locationStatus = ref<LocationStatus>('idle');
const locationLatitude = ref<number | null>(null);
const locationLongitude = ref<number | null>(null);
const locationLabel = ref<string | null>(null);
const locationCity = ref<string | null>(null);
const locationRegion = ref<string | null>(null);
const locationCountry = ref<string | null>(null);
const locationError = ref<string | null>(null);

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
        locationStatus.value = 'unavailable';
        locationError.value = 'Your browser doesn\'t support geolocation.';

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

            locationStatus.value = 'granted';
        },
        (error) => {
            locationStatus.value = error.code === error.PERMISSION_DENIED ? 'denied' : 'unavailable';
            locationError.value = error.code === error.PERMISSION_DENIED
                ? 'Location permission was declined. You can still complete without it.'
                : 'We couldn\'t read your location. You can still complete without it.';
        },
        {
            enableHighAccuracy: false,
            timeout: 10_000,
            maximumAge: 5 * 60 * 1000,
        },
    );
}

const travelRadiusMinutes = computed<number | null>(() => {
    for (const group of props.grouped_answers) {
        if (group.question.type !== 'slider' || group.question.unit !== 'minutes') {
            continue;
        }

        const raw = group.answers[0]?.value;

        if (raw === null || raw === undefined || raw === '') {
            continue;
        }

        const parsed = Number(raw);

        if (Number.isFinite(parsed)) {
            return parsed;
        }
    }

    return null;
});

const locationCardHeadline = computed(() => {
    if (locationStatus.value === 'granted' && locationLabel.value) {
        return `📍 Near ${locationLabel.value}`;
    }

    if (locationStatus.value === 'granted') {
        return '📍 Location captured';
    }

    return "📍 Where are you starting from?";
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

        if (travelRadiusMinutes.value !== null) {
            payload.travel_radius_minutes = travelRadiusMinutes.value;
        }
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

        <!-- Location capture (solo questionnaires only) -->
        <div v-if="questionnaire.is_solo" class="card-premium space-y-4 p-5">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-foreground">{{ locationCardHeadline }}</h2>
                <p class="text-sm text-muted-foreground">
                    We use your device's location to suggest date ideas nearby. Nothing precise is shared — just an approximate area.
                </p>
            </div>

            <div v-if="locationStatus === 'granted'" class="space-y-2 rounded-2xl bg-primary/5 p-4 text-sm">
                <p class="font-semibold text-foreground">
                    ✅ Ready to plan around <span class="text-primary">{{ locationLabel ?? 'your current spot' }}</span>
                </p>
                <p v-if="travelRadiusMinutes !== null" class="text-muted-foreground">
                    We'll stay within roughly {{ formatMinutes(travelRadiusMinutes) }} of you.
                </p>
                <button
                    type="button"
                    class="text-xs font-medium text-primary underline underline-offset-2 hover:opacity-80"
                    @click="requestGeolocation"
                >
                    Refresh location
                </button>
            </div>

            <div v-else-if="locationStatus === 'requesting'" class="rounded-2xl bg-muted/40 p-4 text-sm text-muted-foreground">
                Finding you…
            </div>

            <div v-else class="space-y-3">
                <PrimaryButton full-width @click="requestGeolocation">
                    📍 Use my current location
                </PrimaryButton>
                <p v-if="locationError" class="text-xs text-muted-foreground">
                    {{ locationError }}
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
