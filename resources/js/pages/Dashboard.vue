<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ChallengeCard from '@/components/ChallengeCard.vue';
import CompatibilityCard from '@/components/CompatibilityCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import ProfileCard from '@/components/ProfileCard.vue';
import StatCard from '@/components/StatCard.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import type { Auth } from '@/types';

defineOptions({ layout: MobileLayout });

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 17) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

const challenge = {
    title: 'Cook a new recipe together',
    description:
        'Pick something neither of you has made before and enjoy the process.',
    emoji: '🍳',
    category: 'Quality Time',
};

const savedProfiles = [
    {
        name: 'Romantic Evening',
        emoji: '🌹',
        description: 'Candles, wine, and slow music',
        isFavourite: true,
    },
    {
        name: 'Movie Night',
        emoji: '🎬',
        description: 'Cosy blankets and popcorn',
        isFavourite: false,
    },
    {
        name: 'Anniversary',
        emoji: '💍',
        description: 'Extra special and memorable',
        isFavourite: true,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <div class="card-premium p-6 text-white gradient-primary">
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-white/80">
                        {{ greeting }},
                    </p>
                    <h1 class="text-2xl font-semibold">
                        {{ user.name.split(' ')[0] }} 👋
                    </h1>
                    <p class="mt-2 text-sm text-white/70">Ready for tonight?</p>
                </div>
                <span class="text-4xl" aria-hidden="true">💕</span>
            </div>

            <div class="mt-6">
                <PrimaryButton
                    full-width
                    class="!text-primary !gradient-primary-soft hover:!opacity-100 hover:!shadow-xl"
                >
                    Start Tonight's Questionnaire ❤️
                </PrimaryButton>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <StatCard label="Streak" value="0" emoji="🔥" description="days" />
            <StatCard label="Best" value="0" emoji="🏆" description="days" />
            <StatCard
                label="This month"
                value="0"
                emoji="📅"
                description="done"
            />
        </div>

        <CompatibilityCard :score="null" partner-name="your partner" />

        <div class="space-y-3 card-premium p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">
                    Partner Status
                </h3>
                <span class="text-2xl" aria-hidden="true">👫</span>
            </div>
            <EmptyState
                emoji="💌"
                title="No partner connected"
                description="Invite your partner to start sharing questionnaires and discover your compatibility."
                action-label="Invite Partner"
            />
        </div>

        <ChallengeCard
            :title="challenge.title"
            :description="challenge.description"
            :emoji="challenge.emoji"
            :category="challenge.category"
        />

        <div class="space-y-4 card-premium p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">
                    Saved Profiles
                </h3>
                <button
                    class="text-sm font-medium text-primary hover:underline focus:outline-none"
                >
                    Add new
                </button>
            </div>

            <div v-if="savedProfiles.length > 0" class="space-y-3">
                <ProfileCard
                    v-for="profile in savedProfiles"
                    :key="profile.name"
                    :name="profile.name"
                    :emoji="profile.emoji"
                    :description="profile.description"
                    :is-favourite="profile.isFavourite"
                />
            </div>

            <EmptyState
                v-else
                emoji="💝"
                title="No saved profiles"
                description="Save your favourite questionnaire answers to reuse them on future date nights."
            />
        </div>

        <div class="space-y-3 card-premium p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">
                    Recent Questionnaire
                </h3>
                <span class="text-2xl" aria-hidden="true">📋</span>
            </div>
            <EmptyState
                emoji="📝"
                title="No questionnaires yet"
                description="Complete your first questionnaire to see your history here."
            />
        </div>
    </div>
</template>
