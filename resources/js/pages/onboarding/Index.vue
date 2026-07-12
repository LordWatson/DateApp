<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
const page = usePage<{ flash?: { error?: string } }>();

const currentStep = ref(props.user.onboarding_completed ? 4 : props.user.display_name ? 3 : 2);
const partnerMode = ref<'invite' | 'join' | null>(null);
const inviteSent = ref(false);

const BUILT_IN_AVATARS = [
    '🐱', '🐶', '🦊', '🐻', '🐼', '🐨', '🦁', '🐯', '🐸', '🐙',
    '🦋', '🌸', '🌺', '🌻', '🌹', '🍓', '🍒', '🍑', '🌈', '⭐',
    '🌙', '☀️', '🦄', '🐉', '🦅', '🦚', '🦜', '🐬', '🦋', '🌊',
];

const profileForm = useForm({
    display_name: props.user.display_name ?? '',
    gender: props.user.gender ?? '',
    date_of_birth: props.user.date_of_birth ?? '',
    timezone: props.user.timezone ?? Intl.DateTimeFormat().resolvedOptions().timeZone,
    avatar: props.user.avatar ?? '',
});

const inviteForm = useForm({ email: '' });
const joinForm = useForm({ token: '' });

const selectedAvatar = ref(props.user.avatar ?? '');
const avatarMode = ref<'emoji' | 'upload'>('emoji');

const genderOptions = [
    { value: 'male', label: 'Man', emoji: '👨' },
    { value: 'female', label: 'Woman', emoji: '👩' },
    { value: 'other', label: 'Non-binary', emoji: '🧑' },
    { value: 'prefer_not_to_say', label: 'Prefer not to say', emoji: '🤍' },
];

const flashError = computed(() => page.props.flash?.error);

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
 inviteSent.value = true; currentStep.value = 4; 
},
    });
}

function submitJoin(): void {
    router.get(`/invite/${joinForm.token}`);
}

function completeOnboarding(): void {
    router.post(OnboardingController.complete().url, {}, {
        onSuccess: () => router.visit('/dashboard'),
    });
}

function goToStep(step: number): void {
    if (step < currentStep.value) {
currentStep.value = step;
}
}
</script>

<template>
    <Head title="Welcome to Date Night" />

    <div class="relative min-h-svh bg-background">
        <FloatingHearts />

        <div class="relative z-10 flex min-h-svh flex-col items-center justify-start px-4 py-8">
            <!-- Step Indicator -->
            <div v-if="currentStep < 4" class="mb-8 w-full max-w-md">
                <div class="mb-3 flex items-center justify-between">
                    <button
                        v-if="currentStep > 1"
                        class="flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
                        @click="goToStep(currentStep - 1)"
                    >
                        ← Back
                    </button>
                    <span v-else class="text-sm font-medium text-primary">Date Night</span>
                    <span class="text-sm text-muted-foreground">Step {{ currentStep }} of 4</span>
                </div>
                <StepIndicator :current="currentStep" :total="4" />
            </div>

            <!-- STEP 1: Welcome -->
            <Transition name="step" mode="out-in">
                <div v-if="currentStep === 1" key="step1" class="w-full max-w-md space-y-6">
                    <div class="card-premium p-8 text-center">
                        <div class="mb-6 text-6xl">❤️</div>
                        <h1 class="mb-3 text-3xl font-semibold text-foreground">
                            Welcome to Date Night
                        </h1>
                        <p class="mb-8 text-muted-foreground">
                            Let's help you and your partner create more meaningful evenings together.
                        </p>

                        <div class="mb-8 space-y-3">
                            <div class="flex items-center gap-4 rounded-2xl bg-primary/5 p-4 text-left">
                                <span class="text-2xl">❤️</span>
                                <div>
                                    <p class="font-semibold text-foreground">Share your mood</p>
                                    <p class="text-sm text-muted-foreground">Tell your partner how you're feeling tonight</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 rounded-2xl bg-secondary/5 p-4 text-left">
                                <span class="text-2xl">✨</span>
                                <div>
                                    <p class="font-semibold text-foreground">Discover new ideas</p>
                                    <p class="text-sm text-muted-foreground">Find activities you'll both love</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 rounded-2xl bg-primary/5 p-4 text-left">
                                <span class="text-2xl">💕</span>
                                <div>
                                    <p class="font-semibold text-foreground">Stay connected</p>
                                    <p class="text-sm text-muted-foreground">Build streaks and track your journey</p>
                                </div>
                            </div>
                        </div>

                        <PrimaryButton full-width @click="currentStep = 2">
                            Let's Begin ✨
                        </PrimaryButton>
                    </div>
                </div>
            </Transition>

            <!-- STEP 2: Create Profile -->
            <Transition name="step" mode="out-in">
                <div v-if="currentStep === 2" key="step2" class="w-full max-w-md space-y-6">
                    <div class="card-premium p-8">
                        <div class="mb-6 text-center">
                            <div class="mb-3 text-4xl">👤</div>
                            <h2 class="text-2xl font-semibold text-foreground">Create your profile</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Tell us a little about yourself</p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submitProfile">
                            <!-- Avatar -->
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-foreground">Your avatar</label>
                                <div class="mb-3 flex justify-center">
                                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-primary/10 text-5xl shadow-inner">
                                        {{ selectedAvatar || '👤' }}
                                    </div>
                                </div>
                                <div class="mb-2 flex gap-2">
                                    <button
                                        type="button"
                                        :class="['flex-1 rounded-2xl py-2 text-sm font-medium transition-all', avatarMode === 'emoji' ? 'gradient-primary text-white' : 'bg-muted text-muted-foreground']"
                                        @click="avatarMode = 'emoji'"
                                    >Choose emoji</button>
                                </div>
                                <div class="grid grid-cols-6 gap-2">
                                    <button
                                        v-for="emoji in BUILT_IN_AVATARS"
                                        :key="emoji"
                                        type="button"
                                        :class="['flex h-10 w-10 items-center justify-center rounded-xl text-xl transition-all hover:scale-110', selectedAvatar === emoji ? 'ring-2 ring-primary bg-primary/10' : 'bg-muted/50']"
                                        @click="selectAvatar(emoji)"
                                    >{{ emoji }}</button>
                                </div>
                            </div>

                            <!-- Display Name -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-foreground" for="display_name">
                                    Display name <span class="text-primary">*</span>
                                </label>
                                <input
                                    id="display_name"
                                    v-model="profileForm.display_name"
                                    type="text"
                                    placeholder="How should we call you?"
                                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    required
                                />
                                <p v-if="profileForm.errors.display_name" class="mt-1 text-sm text-destructive">{{ profileForm.errors.display_name }}</p>
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-foreground">
                                    Gender <span class="text-primary">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="option in genderOptions"
                                        :key="option.value"
                                        type="button"
                                        :class="['flex items-center gap-2 rounded-2xl border p-3 text-sm font-medium transition-all', profileForm.gender === option.value ? 'border-primary bg-primary/10 text-primary' : 'border-border bg-background text-foreground hover:border-primary/50']"
                                        @click="profileForm.gender = option.value"
                                    >
                                        <span>{{ option.emoji }}</span>
                                        <span>{{ option.label }}</span>
                                    </button>
                                </div>
                                <p v-if="profileForm.errors.gender" class="mt-1 text-sm text-destructive">{{ profileForm.errors.gender }}</p>
                            </div>

                            <!-- Date of Birth -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-foreground" for="date_of_birth">
                                    Date of birth <span class="text-muted-foreground text-xs font-normal">(optional)</span>
                                </label>
                                <input
                                    id="date_of_birth"
                                    v-model="profileForm.date_of_birth"
                                    type="date"
                                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <!-- Timezone -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-foreground" for="timezone">Timezone</label>
                                <input
                                    id="timezone"
                                    v-model="profileForm.timezone"
                                    type="text"
                                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <PrimaryButton type="submit" full-width :disabled="profileForm.processing">
                                {{ profileForm.processing ? 'Saving...' : 'Continue →' }}
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </Transition>

            <!-- STEP 3: Partner Setup -->
            <Transition name="step" mode="out-in">
                <div v-if="currentStep === 3" key="step3" class="w-full max-w-md space-y-6">
                    <div class="card-premium p-8">
                        <div class="mb-6 text-center">
                            <div class="mb-3 text-4xl">💑</div>
                            <h2 class="text-2xl font-semibold text-foreground">Connect with your partner</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Choose how you'd like to get started</p>
                        </div>

                        <div v-if="!partnerMode" class="space-y-4">
                            <button
                                class="w-full rounded-3xl border-2 border-primary/20 bg-primary/5 p-6 text-left transition-all hover:border-primary hover:bg-primary/10 active:scale-[0.98]"
                                @click="partnerMode = 'invite'"
                            >
                                <div class="mb-2 text-3xl">❤️</div>
                                <h3 class="text-lg font-semibold text-foreground">Invite My Partner</h3>
                                <p class="mt-1 text-sm text-muted-foreground">Send them a beautiful invitation email</p>
                            </button>

                            <button
                                class="w-full rounded-3xl border-2 border-secondary/20 bg-secondary/5 p-6 text-left transition-all hover:border-secondary hover:bg-secondary/10 active:scale-[0.98]"
                                @click="partnerMode = 'join'"
                            >
                                <div class="mb-2 text-3xl">💌</div>
                                <h3 class="text-lg font-semibold text-foreground">I Have an Invite</h3>
                                <p class="mt-1 text-sm text-muted-foreground">Enter your invitation token to connect</p>
                            </button>

                            <SecondaryButton full-width @click="currentStep = 4">
                                Skip for now
                            </SecondaryButton>
                        </div>

                        <!-- Invite Partner Form -->
                        <div v-else-if="partnerMode === 'invite'" class="space-y-4">
                            <button class="mb-2 text-sm text-muted-foreground hover:text-foreground" @click="partnerMode = null">← Back</button>
                            <div v-if="flashError" class="rounded-2xl bg-destructive/10 p-4 text-sm text-destructive">{{ flashError }}</div>
                            <form class="space-y-4" @submit.prevent="submitInvite">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-foreground" for="partner_email">Partner's email</label>
                                    <input
                                        id="partner_email"
                                        v-model="inviteForm.email"
                                        type="email"
                                        placeholder="their@email.com"
                                        class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                        required
                                    />
                                    <p v-if="inviteForm.errors.email" class="mt-1 text-sm text-destructive">{{ inviteForm.errors.email }}</p>
                                </div>
                                <PrimaryButton type="submit" full-width :disabled="inviteForm.processing">
                                    {{ inviteForm.processing ? 'Sending...' : 'Send Invitation 💌' }}
                                </PrimaryButton>
                            </form>
                        </div>

                        <!-- Join with Token -->
                        <div v-else-if="partnerMode === 'join'" class="space-y-4">
                            <button class="mb-2 text-sm text-muted-foreground hover:text-foreground" @click="partnerMode = null">← Back</button>
                            <p class="text-sm text-muted-foreground">Paste the invitation link or token you received:</p>
                            <div>
                                <input
                                    v-model="joinForm.token"
                                    type="text"
                                    placeholder="Paste invitation token here"
                                    class="w-full rounded-2xl border border-border bg-background px-4 py-3 text-foreground placeholder-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>
                            <PrimaryButton full-width :disabled="!joinForm.token" @click="submitJoin">
                                Connect ❤️
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- STEP 4: Success -->
            <Transition name="step" mode="out-in">
                <div v-if="currentStep === 4" key="step4" class="w-full max-w-md">
                    <div class="card-premium p-8 text-center">
                        <div v-if="inviteSent || user.partner_id">
                            <SuccessAnimation
                                :title="user.partner_id ? 'You\'re Connected!' : 'Invitation Sent!'"
                                :message="user.partner_id ? 'You and your partner are now connected on Date Night.' : 'We\'ll notify you as soon as your partner joins.'"
                                :emoji="user.partner_id ? '❤️' : '💌'"
                            />
                        </div>
                        <div v-else>
                            <div class="mb-6 text-6xl">⏳</div>
                            <h2 class="mb-3 text-2xl font-semibold text-foreground">Waiting for your partner ❤️</h2>
                            <p class="mb-8 text-muted-foreground">We'll let you know as soon as they join.</p>
                            <div class="mb-8 flex justify-center gap-2">
                                <div v-for="i in 3" :key="i" class="h-2 w-2 animate-bounce rounded-full bg-primary" :style="{ animationDelay: `${i * 0.15}s` }" />
                            </div>
                        </div>

                        <PrimaryButton full-width @click="completeOnboarding">
                            Continue to Dashboard →
                        </PrimaryButton>
                    </div>
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
