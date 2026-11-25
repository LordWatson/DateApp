<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowRight, ChevronLeft, ChevronRight } from '@lucide/vue';
import { ref } from 'vue';
import OnboardingController from '@/actions/App/Http/Controllers/OnboardingController';
import FloatingHearts from '@/components/FloatingHearts.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import StepIndicator from '@/components/StepIndicator.vue';
import SuccessAnimation from '@/components/SuccessAnimation.vue';
import type { User } from '@/types';

interface Props {
    user: User;
}

const props = defineProps<Props>();

const currentStep = ref(
    props.user.onboarding_completed ? 4 : props.user.display_name ? 3 : 2,
);
const partnerMode = ref<'invite' | 'join' | null>(null);
const inviteSent = ref(false);

const BUILT_IN_AVATARS = [
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
    '🦋',
    '🌊',
];

const profileForm = useForm({
    display_name: props.user.display_name ?? '',
    gender: props.user.gender ?? '',
    date_of_birth: props.user.date_of_birth ?? '',
    timezone:
        props.user.timezone ?? Intl.DateTimeFormat().resolvedOptions().timeZone,
    avatar: props.user.avatar ?? '',
});

const inviteForm = useForm({ email: '' });
const joinForm = useForm({ token: '' });

const selectedAvatar = ref(props.user.avatar ?? '');

const genderOptions = [
    { value: 'male', label: 'Man', emoji: '👨' },
    { value: 'female', label: 'Woman', emoji: '👩' },
    { value: 'other', label: 'Non-binary', emoji: '🧑' },
    { value: 'prefer_not_to_say', label: 'Prefer not to say', emoji: '🤍' },
];

function selectAvatar(emoji: string): void {
    selectedAvatar.value = emoji;
    profileForm.avatar = emoji;
}

function submitProfile(): void {
    profileForm.post(OnboardingController.completeProfile().url, {
        onSuccess: () => {
            currentStep.value = 3;
        },
    });
}

function submitInvite(): void {
    inviteForm.post(OnboardingController.sendInvite().url, {
        onSuccess: () => {
            inviteSent.value = true;
            currentStep.value = 4;
        },
    });
}

function submitJoin(): void {
    // Accept the bare token or the whole emailed link.
    const token = joinForm.token.trim().split('/').filter(Boolean).pop() ?? '';

    router.get(`/invite/${token}`);
}

function completeOnboarding(): void {
    router.post(
        OnboardingController.complete().url,
        {},
        {
            onSuccess: () => router.visit('/dashboard'),
        },
    );
}

function goToStep(step: number): void {
    if (step < currentStep.value) {
        currentStep.value = step;
    }
}

const labelClass = 'mb-1.5 block text-[13px] font-medium text-foreground/80';
const focusClass =
    'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';
const backClass =
    '-ml-1.5 inline-flex min-h-9 items-center gap-1 rounded-full pr-3 pl-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground';
const choiceClass =
    'flex w-full items-center gap-3.5 rounded-2xl border border-border bg-card p-4 text-left transition-colors hover:border-primary/40 hover:bg-accent active:scale-[0.98]';
</script>

<template>
    <Head title="Welcome to Date Night" />

    <div class="relative min-h-svh">
        <FloatingHearts />

        <div
            class="relative z-10 mx-auto flex min-h-svh w-full max-w-md flex-col px-4 pt-6 pb-10"
        >
            <!-- Progress -->
            <div v-if="currentStep < 4" class="mb-8 space-y-4">
                <div class="flex min-h-9 items-center justify-between">
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        :class="[backClass, focusClass]"
                        @click="goToStep(currentStep - 1)"
                    >
                        <ChevronLeft class="size-4" aria-hidden="true" />
                        Back
                    </button>
                    <span
                        v-else
                        class="font-display text-lg font-semibold text-foreground"
                        >Date Night</span
                    >
                    <span class="text-xs text-muted-foreground tabular-nums"
                        >Step {{ currentStep }} of 4</span
                    >
                </div>
                <StepIndicator :current="currentStep" :total="4" />
            </div>

            <!-- Step 1: welcome -->
            <Transition name="step" mode="out-in">
                <div
                    v-if="currentStep === 1"
                    key="step1"
                    class="space-y-6 card-premium p-6 sm:p-8"
                >
                    <div class="space-y-2">
                        <p class="eyebrow">Welcome</p>
                        <h1
                            class="text-[2rem] leading-[1.1] font-semibold text-foreground"
                        >
                            Welcome to Date Night
                        </h1>
                        <p
                            class="text-[15px] leading-relaxed text-muted-foreground"
                        >
                            Let's help you and your partner create more
                            meaningful evenings together.
                        </p>
                    </div>

                    <div class="hairline-gold" />

                    <ul class="space-y-4">
                        <li class="flex items-center gap-3.5">
                            <span
                                class="icon-tile h-11 w-11 text-xl"
                                aria-hidden="true"
                                >❤️</span
                            >
                            <div>
                                <p class="font-medium text-foreground">
                                    Share your mood
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Tell your partner how you're feeling tonight
                                </p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3.5">
                            <span
                                class="icon-tile h-11 w-11 text-xl"
                                aria-hidden="true"
                                >✨</span
                            >
                            <div>
                                <p class="font-medium text-foreground">
                                    Discover new ideas
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Find activities you'll both love
                                </p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3.5">
                            <span
                                class="icon-tile h-11 w-11 text-xl"
                                aria-hidden="true"
                                >💕</span
                            >
                            <div>
                                <p class="font-medium text-foreground">
                                    Stay connected
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Build streaks and track your journey
                                </p>
                            </div>
                        </li>
                    </ul>

                    <PrimaryButton full-width @click="currentStep = 2">
                        Let's begin
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </PrimaryButton>
                </div>
            </Transition>

            <!-- Step 2: create profile -->
            <Transition name="step" mode="out-in">
                <div
                    v-if="currentStep === 2"
                    key="step2"
                    class="space-y-6 card-premium p-6 sm:p-8"
                >
                    <div class="space-y-2">
                        <p class="eyebrow">About you</p>
                        <h1
                            class="text-[1.75rem] leading-[1.15] font-semibold text-foreground"
                        >
                            Create your profile
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Tell us a little about yourself
                        </p>
                    </div>

                    <form class="space-y-5" @submit.prevent="submitProfile">
                        <!-- Avatar -->
                        <div role="group" aria-labelledby="avatar-label">
                            <div class="mb-3 flex items-center gap-3.5">
                                <span
                                    class="icon-tile h-14 w-14 text-3xl"
                                    aria-hidden="true"
                                    >{{ selectedAvatar || '👤' }}</span
                                >
                                <div>
                                    <p
                                        id="avatar-label"
                                        class="text-[13px] font-medium text-foreground/80"
                                    >
                                        Your avatar
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Choose emoji
                                    </p>
                                </div>
                            </div>
                            <div
                                class="grid grid-cols-6 justify-items-center gap-2"
                            >
                                <button
                                    v-for="(emoji, index) in BUILT_IN_AVATARS"
                                    :key="index"
                                    type="button"
                                    :class="[
                                        'flex h-10 w-10 items-center justify-center rounded-xl text-xl transition-colors',
                                        focusClass,
                                        selectedAvatar === emoji
                                            ? 'bg-accent ring-2 ring-primary'
                                            : 'bg-muted hover:bg-accent',
                                    ]"
                                    :aria-pressed="selectedAvatar === emoji"
                                    :aria-label="`Avatar ${emoji}`"
                                    @click="selectAvatar(emoji)"
                                >
                                    {{ emoji }}
                                </button>
                            </div>
                        </div>

                        <!-- Display name -->
                        <div>
                            <label :class="labelClass" for="display_name">
                                Display name
                                <span class="text-primary">*</span>
                            </label>
                            <input
                                id="display_name"
                                v-model="profileForm.display_name"
                                type="text"
                                placeholder="How should we call you?"
                                class="input-premium"
                                required
                            />
                            <p
                                v-if="profileForm.errors.display_name"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ profileForm.errors.display_name }}
                            </p>
                        </div>

                        <!-- Gender -->
                        <div role="group" aria-labelledby="gender-label">
                            <p id="gender-label" :class="labelClass">
                                Gender <span class="text-primary">*</span>
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="option in genderOptions"
                                    :key="option.value"
                                    type="button"
                                    :class="[
                                        'chip w-full justify-center',
                                        focusClass,
                                    ]"
                                    :aria-pressed="
                                        profileForm.gender === option.value
                                    "
                                    @click="profileForm.gender = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                            <p
                                v-if="profileForm.errors.gender"
                                class="mt-1 text-sm text-destructive"
                            >
                                {{ profileForm.errors.gender }}
                            </p>
                        </div>

                        <!-- Date of birth -->
                        <div>
                            <label :class="labelClass" for="date_of_birth">
                                Date of birth
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                    >(optional)</span
                                >
                            </label>
                            <input
                                id="date_of_birth"
                                v-model="profileForm.date_of_birth"
                                type="date"
                                class="input-premium"
                            />
                        </div>

                        <!-- Timezone -->
                        <div>
                            <label :class="labelClass" for="timezone"
                                >Timezone</label
                            >
                            <input
                                id="timezone"
                                v-model="profileForm.timezone"
                                type="text"
                                class="input-premium"
                            />
                        </div>

                        <PrimaryButton
                            type="submit"
                            full-width
                            :disabled="profileForm.processing"
                        >
                            <template v-if="profileForm.processing"
                                >Saving...</template
                            >
                            <template v-else>
                                Continue
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </template>
                        </PrimaryButton>
                    </form>
                </div>
            </Transition>

            <!-- Step 3: partner setup -->
            <Transition name="step" mode="out-in">
                <div
                    v-if="currentStep === 3"
                    key="step3"
                    class="space-y-6 card-premium p-6 sm:p-8"
                >
                    <div class="space-y-2">
                        <p class="eyebrow">Your partner</p>
                        <h1
                            class="text-[1.75rem] leading-[1.15] font-semibold text-foreground"
                        >
                            Connect with your partner
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Choose how you'd like to get started
                        </p>
                    </div>

                    <div v-if="!partnerMode" class="space-y-3">
                        <button
                            type="button"
                            :class="[choiceClass, focusClass]"
                            @click="partnerMode = 'invite'"
                        >
                            <span
                                class="icon-tile h-12 w-12 text-2xl"
                                aria-hidden="true"
                                >❤️</span
                            >
                            <span class="block min-w-0 flex-1">
                                <span class="block font-medium text-foreground"
                                    >Invite my partner</span
                                >
                                <span
                                    class="block text-sm text-muted-foreground"
                                    >Send them a beautiful invitation
                                    email</span
                                >
                            </span>
                            <ChevronRight
                                class="size-4 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </button>

                        <button
                            type="button"
                            :class="[choiceClass, focusClass]"
                            @click="partnerMode = 'join'"
                        >
                            <span
                                class="icon-tile h-12 w-12 text-2xl"
                                aria-hidden="true"
                                >💌</span
                            >
                            <span class="block min-w-0 flex-1">
                                <span class="block font-medium text-foreground"
                                    >I have an invite</span
                                >
                                <span
                                    class="block text-sm text-muted-foreground"
                                    >Enter your invitation token to
                                    connect</span
                                >
                            </span>
                            <ChevronRight
                                class="size-4 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </button>

                        <SecondaryButton
                            variant="ghost"
                            full-width
                            @click="currentStep = 4"
                        >
                            Skip for now
                        </SecondaryButton>
                    </div>

                    <!-- Invite partner form -->
                    <div v-else-if="partnerMode === 'invite'" class="space-y-4">
                        <button
                            type="button"
                            :class="[backClass, focusClass]"
                            @click="partnerMode = null"
                        >
                            <ChevronLeft class="size-4" aria-hidden="true" />
                            Back
                        </button>
                        <form class="space-y-4" @submit.prevent="submitInvite">
                            <div>
                                <label :class="labelClass" for="partner_email"
                                    >Partner's email</label
                                >
                                <input
                                    id="partner_email"
                                    v-model="inviteForm.email"
                                    type="email"
                                    placeholder="their@email.com"
                                    class="input-premium"
                                    required
                                />
                                <p
                                    v-if="inviteForm.errors.email"
                                    class="mt-1 text-sm text-destructive"
                                >
                                    {{ inviteForm.errors.email }}
                                </p>
                            </div>
                            <PrimaryButton
                                type="submit"
                                full-width
                                :disabled="inviteForm.processing"
                            >
                                {{
                                    inviteForm.processing
                                        ? 'Sending...'
                                        : 'Send invitation'
                                }}
                            </PrimaryButton>
                        </form>
                    </div>

                    <!-- Join with token -->
                    <div v-else-if="partnerMode === 'join'" class="space-y-4">
                        <button
                            type="button"
                            :class="[backClass, focusClass]"
                            @click="partnerMode = null"
                        >
                            <ChevronLeft class="size-4" aria-hidden="true" />
                            Back
                        </button>
                        <div>
                            <label :class="labelClass" for="invite_token"
                                >Invitation token</label
                            >
                            <input
                                id="invite_token"
                                v-model="joinForm.token"
                                type="text"
                                placeholder="Paste invitation token here"
                                class="input-premium"
                                aria-describedby="invite_token_help"
                            />
                            <p
                                id="invite_token_help"
                                class="mt-1.5 text-xs text-muted-foreground"
                            >
                                Paste the invitation link or token you received.
                            </p>
                        </div>
                        <PrimaryButton
                            full-width
                            :disabled="!joinForm.token"
                            @click="submitJoin"
                        >
                            Connect
                        </PrimaryButton>
                    </div>
                </div>
            </Transition>

            <!-- Step 4: success -->
            <Transition name="step" mode="out-in">
                <div
                    v-if="currentStep === 4"
                    key="step4"
                    class="my-auto card-premium p-6 text-center sm:p-8"
                >
                    <div v-if="inviteSent || user.partner_id">
                        <SuccessAnimation
                            :title="
                                user.partner_id
                                    ? 'You\'re connected'
                                    : 'Invitation sent'
                            "
                            :message="
                                user.partner_id
                                    ? 'You and your partner are now connected on Date Night.'
                                    : 'We\'ll notify you as soon as your partner joins.'
                            "
                            :emoji="user.partner_id ? '❤️' : '💌'"
                        />
                    </div>
                    <div v-else class="flex flex-col items-center gap-4 py-6">
                        <span
                            class="icon-tile h-16 w-16 text-3xl"
                            aria-hidden="true"
                            >⏳</span
                        >
                        <div class="space-y-2">
                            <p class="eyebrow">All set</p>
                            <h1
                                class="text-[1.75rem] leading-[1.15] font-semibold text-foreground"
                            >
                                Waiting for your partner
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                We'll let you know as soon as they join.
                            </p>
                        </div>
                        <div
                            class="flex justify-center gap-2"
                            aria-hidden="true"
                        >
                            <div
                                v-for="i in 3"
                                :key="i"
                                class="size-1.5 animate-pulse rounded-full bg-primary/60"
                                :style="{ animationDelay: `${i * 0.15}s` }"
                            />
                        </div>
                    </div>

                    <PrimaryButton
                        full-width
                        class="mt-4"
                        @click="completeOnboarding"
                    >
                        Continue to dashboard
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </PrimaryButton>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.step-enter-active,
.step-leave-active {
    transition: all 0.3s ease;
}
.step-enter-from {
    opacity: 0;
    transform: translateX(24px);
}
.step-leave-to {
    opacity: 0;
    transform: translateX(-24px);
}
</style>
