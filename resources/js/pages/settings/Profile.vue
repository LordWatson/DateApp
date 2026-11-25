<script setup lang="ts">
import { Form, Head, useForm, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import AiConsentCard from '@/components/AiConsentCard.vue';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { download, store as requestExport } from '@/routes/data-export';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const props = defineProps<{
    mustVerifyEmail: boolean;
    status?: string | null;
    gender: string | null;
    exportReady: boolean;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

// The same choice onboarding offers (pages/onboarding/Index.vue).
const AVATARS = [
    '🐱',
    '🐶',
    '🦊',
    '🐻',
    '🐼',
    '🐨',
    '🦁',
    '🐯',
    '🐸',
    '🐙',
    '🦋',
    '🌸',
    '🌺',
    '🌻',
    '🌹',
    '🍓',
    '🍒',
    '🍑',
    '🌈',
    '⭐',
    '🌙',
    '☀️',
    '🦄',
    '🐉',
    '🦅',
    '🦚',
    '🦜',
    '🐬',
    '🌊',
];

const genderOptions = [
    { value: 'male', label: 'Man' },
    { value: 'female', label: 'Woman' },
    { value: 'other', label: 'Non-binary' },
    { value: 'prefer_not_to_say', label: 'Prefer not to say' },
];

const avatar = ref(user.value.avatar ?? '');
const gender = ref(props.gender ?? '');
const timezone = ref(user.value.timezone ?? 'UTC');

const timezones = [
    ...new Set(['UTC', timezone.value, ...Intl.supportedValuesOf('timeZone')]),
];

const exportForm = useForm({});

const focusClass =
    'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';
</script>

<template>
    <Head title="Profile settings" />

    <h2 class="sr-only">Profile settings</h2>

    <div class="space-y-5 card-premium p-5">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name, email address and how you appear to your partner"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="display_name">Display name</Label>
                <Input
                    id="display_name"
                    name="display_name"
                    :default-value="user.display_name ?? ''"
                    maxlength="50"
                    autocomplete="nickname"
                    placeholder="What your partner sees"
                />
                <p class="text-xs text-muted-foreground">
                    Leave empty to use your name.
                </p>
                <InputError :message="errors.display_name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-2 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="font-medium text-primary underline decoration-primary/30 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    role="status"
                    class="mt-3 rounded-2xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-medium text-success"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="grid gap-2" role="group" aria-labelledby="avatar-label">
                <div class="flex items-center gap-3">
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        aria-hidden="true"
                        >{{ avatar || '👤' }}</span
                    >
                    <Label id="avatar-label" as="p">Avatar</Label>
                </div>
                <input type="hidden" name="avatar" :value="avatar" />
                <div class="grid grid-cols-6 justify-items-center gap-2">
                    <button
                        v-for="emoji in AVATARS"
                        :key="emoji"
                        type="button"
                        :class="[
                            'flex h-10 w-10 items-center justify-center rounded-xl text-xl transition-colors',
                            focusClass,
                            avatar === emoji
                                ? 'bg-accent ring-2 ring-primary'
                                : 'bg-muted hover:bg-accent',
                        ]"
                        :aria-pressed="avatar === emoji"
                        :aria-label="`Avatar ${emoji}`"
                        @click="avatar = emoji"
                    >
                        {{ emoji }}
                    </button>
                </div>
                <InputError :message="errors.avatar" />
            </div>

            <div class="grid gap-2" role="group" aria-labelledby="gender-label">
                <Label id="gender-label" as="p">Gender</Label>
                <input type="hidden" name="gender" :value="gender" />
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="option in genderOptions"
                        :key="option.value"
                        type="button"
                        :class="['chip w-full justify-center', focusClass]"
                        :aria-pressed="gender === option.value"
                        @click="gender = option.value"
                    >
                        {{ option.label }}
                    </button>
                </div>
                <InputError :message="errors.gender" />
            </div>

            <div class="grid gap-2">
                <Label for="timezone">Timezone</Label>
                <select
                    id="timezone"
                    v-model="timezone"
                    name="timezone"
                    class="input-premium"
                >
                    <option v-for="zone in timezones" :key="zone" :value="zone">
                        {{ zone.replaceAll('_', ' ') }}
                    </option>
                </select>
                <InputError :message="errors.timezone" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>

    <AiConsentCard />

    <div class="space-y-5 card-premium p-5">
        <Heading
            variant="small"
            title="Download my data"
            description="One file with your profile, questionnaire answers, the date nights you took part in, your moments and private notes, your calendar events, love notes you sent and received, achievements and notifications. Your partner's own records are not included."
        />

        <p class="text-sm leading-relaxed text-muted-foreground">
            We prepare the file in the background and send you a notification
            when it is ready. It stays available for 24 hours, and you can ask
            for a new one once an hour.
        </p>

        <div class="flex flex-wrap items-center gap-3">
            <Button
                variant="outline"
                :disabled="exportForm.processing"
                @click="
                    exportForm.post(requestExport().url, {
                        preserveScroll: true,
                    })
                "
                >Prepare my data</Button
            >
            <!-- A plain link: the response is a file, not an Inertia page. -->
            <Button v-if="exportReady" as-child>
                <a :href="download().url" download>
                    <Download class="size-4" aria-hidden="true" />
                    Download file
                </a>
            </Button>
        </div>
    </div>

    <DeleteUser />
</template>
