<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PartnerController from '@/actions/App/Http/Controllers/PartnerController';
import ChallengeCard from '@/components/ChallengeCard.vue';
import CompatibilityCard from '@/components/CompatibilityCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import StatCard from '@/components/StatCard.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import type { Auth } from '@/types';

defineOptions({ layout: MobileLayout });

interface Partner {
    id: number;
    name: string;
    display_name?: string;
    avatar?: string;
}

interface PendingInvitation {
    email: string;
    expires_at: string;
}

interface Stats {
    current_streak: number;
    longest_streak: number;
    monthly_completions: number;
}

interface TodayChallenge {
    id: number;
    title: string;
    description?: string;
    emoji: string;
    difficulty: string;
}

interface SavedProfile {
    id: number;
    name: string;
    emoji: string;
    colour: string;
}

interface RecentQuestionnaire {
    id: number;
    title: string;
    emoji: string;
    slug: string;
    completed_at: string | null;
}

interface RecentDateNight {
    id: number;
    theme: string | null;
    theme_emoji: string | null;
    compatibility_score: number | null;
    questionnaire_title: string | null;
    created_at: string | null;
}

interface Props {
    partner: Partner | null;
    pendingInvitation: PendingInvitation | null;
    stats: Stats;
    todayChallenge: TodayChallenge | null;
    savedProfiles: SavedProfile[];
    recentQuestionnaires: RecentQuestionnaire[];
    recentDateNights: RecentDateNight[];
}

const props = defineProps<Props>();
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

const firstName = computed(() => {
    const name = user.value.display_name ?? user.value.name;

    return name.split(' ')[0];
});

const partnerStatus = computed(() => {
    if (props.partner) {
        return 'connected';
    }

    if (props.pendingInvitation) {
        return 'pending';
    }

    return 'none';
});

function invitePartner(): void {
    router.visit(PartnerController.index().url);
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-4 px-4 py-6 pb-24">
        <!-- Hero Card -->
        <div
            class="overflow-hidden card-premium p-6 text-white gradient-primary"
        >
            <div class="flex items-start justify-between">
                <div class="space-y-1">
                    <p class="text-sm font-medium text-white/80">
                        {{ greeting }},
                    </p>
                    <h1 class="text-2xl font-semibold">{{ firstName }} ❤️</h1>
                    <p class="mt-1 text-sm text-white/70">
                        Ready to plan tonight?
                    </p>
                </div>
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-3xl"
                >
                    {{ user.avatar || '💕' }}
                </div>
            </div>
            <div class="mt-5">
                <PrimaryButton
                    full-width
                    class="!bg-white text-white/80 hover:!bg-white/90"
                    @click="router.visit('/questionnaires')"
                >
                    Start Tonight's Questionnaire ✨
                </PrimaryButton>
            </div>
        </div>

        <!-- Partner Status Card -->
        <div class="card-premium p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">Partner</h3>
                <span class="text-2xl" aria-hidden="true">💑</span>
            </div>

            <!-- Connected -->
            <div
                v-if="partnerStatus === 'connected'"
                class="flex items-center gap-4"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-2xl"
                >
                    {{ partner?.avatar || '💕' }}
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-foreground">
                        {{ partner?.display_name ?? partner?.name }}
                    </p>
                    <p class="text-sm text-success">● Connected</p>
                </div>
            </div>

            <!-- Pending Invitation -->
            <div v-else-if="partnerStatus === 'pending'" class="space-y-3">
                <div
                    class="flex items-center gap-3 rounded-2xl bg-warning/10 p-3"
                >
                    <span class="text-xl">⏳</span>
                    <div>
                        <p class="text-sm font-semibold text-foreground">
                            Invitation pending
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Sent to {{ pendingInvitation?.email }}
                        </p>
                    </div>
                </div>
                <PrimaryButton full-width @click="invitePartner">
                    Manage Invitation
                </PrimaryButton>
            </div>

            <!-- No Partner -->
            <div v-else>
                <EmptyState
                    emoji="💌"
                    title="No partner connected"
                    description="Invite your partner to start sharing questionnaires and discover your compatibility."
                    action-label="Invite Partner"
                    @action="invitePartner"
                />
            </div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-3 gap-3">
            <StatCard
                label="Streak"
                :value="String(stats.current_streak)"
                emoji="🔥"
                description="days"
            />
            <StatCard
                label="Best"
                :value="String(stats.longest_streak)"
                emoji="🏆"
                description="days"
            />
            <StatCard
                :label="
                    new Date().toLocaleString('default', { month: 'short' })
                "
                :value="String(stats.monthly_completions)"
                emoji="📅"
                description="done"
            />
        </div>

        <!-- Today's Challenge -->
        <div v-if="todayChallenge">
            <ChallengeCard
                :title="todayChallenge.title"
                :description="todayChallenge.description ?? ''"
                :emoji="todayChallenge.emoji"
                :category="todayChallenge.difficulty"
            />
        </div>
        <div v-else class="card-premium p-6">
            <EmptyState
                emoji="✨"
                title="No challenge today"
                description="Check back soon for your daily romantic challenge."
            />
        </div>

        <!-- Love Notes -->
        <Link
            href="/love-notes"
            class="group relative block card-hover overflow-hidden card-premium p-6"
        >
            <div
                class="absolute inset-0 bg-gradient-to-br from-pink-500 to-purple-500 opacity-5 transition-opacity group-hover:opacity-10"
                aria-hidden="true"
            />
            <div class="relative flex items-center gap-4">
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-3xl"
                    aria-hidden="true"
                >
                    💌
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-foreground">
                        Love Notes
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Send a sweet message to your partner
                    </p>
                </div>
                <span class="text-muted-foreground" aria-hidden="true">›</span>
            </div>
        </Link>

        <!-- Recent Questionnaire -->
        <div class="card-premium p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">
                    Recent Questionnaire
                </h3>
                <span class="text-2xl" aria-hidden="true">📋</span>
            </div>

            <div v-if="recentQuestionnaires.length > 0" class="space-y-3">
                <Link
                    v-for="q in recentQuestionnaires"
                    :key="q.id"
                    :href="`/questionnaires/${q.slug}/partner-answers`"
                    class="flex cursor-pointer items-center gap-3 rounded-2xl p-3 transition-all hover:bg-muted/50 active:scale-[0.98]"
                >
                    <span class="text-2xl" aria-hidden="true">{{
                        q.emoji
                    }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-foreground">
                            {{ q.title }}
                        </p>
                        <p
                            v-if="q.completed_at"
                            class="text-xs text-muted-foreground"
                        >
                            {{ new Date(q.completed_at).toLocaleDateString() }}
                        </p>
                    </div>
                    <span class="text-muted-foreground" aria-hidden="true"
                        >›</span
                    >
                </Link>
            </div>

            <EmptyState
                v-else
                emoji="📝"
                title="Tonight is waiting ❤️"
                description="Complete your first questionnaire to see your history here."
            />
        </div>

        <!-- Recent Date Nights -->
        <div class="card-premium p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">
                    Recent Date Nights
                </h3>
                <Link
                    href="/date-night/history"
                    class="text-sm font-medium text-primary hover:underline focus:outline-none"
                >
                    View all
                </Link>
            </div>

            <div v-if="recentDateNights.length > 0" class="space-y-3">
                <Link
                    v-for="plan in recentDateNights"
                    :key="plan.id"
                    :href="`/date-night/${plan.id}`"
                    class="flex cursor-pointer items-center gap-3 rounded-2xl p-3 transition-all hover:bg-muted/50 active:scale-[0.98]"
                >
                    <span class="text-2xl" aria-hidden="true">{{
                        plan.theme_emoji || '💖'
                    }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-foreground">
                            {{ plan.theme || 'Date Night' }}
                        </p>
                        <p
                            v-if="plan.created_at"
                            class="text-xs text-muted-foreground"
                        >
                            {{
                                new Date(
                                    plan.created_at,
                                ).toLocaleDateString()
                            }}
                            <span
                                v-if="plan.compatibility_score !== null"
                                class="ml-1"
                            >
                                · {{ plan.compatibility_score }}% match
                            </span>
                        </p>
                    </div>
                    <span class="text-muted-foreground" aria-hidden="true"
                        >›</span
                    >
                </Link>
            </div>

            <EmptyState
                v-else
                emoji="💖"
                title="No date nights yet"
                description="Complete a questionnaire with your partner to generate your first date night plan."
            />
        </div>

        <!-- Compatibility Card -->
<!--
        <CompatibilityCard
            :score="null"
            :partner-name="
                partner?.display_name ?? partner?.name ?? 'your partner'
            "
        />
-->

        <!-- Saved Profiles -->
        <div class="card-premium p-6">
            <div class="mb-4 flex items-center justify-between">
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
                <div
                    v-for="profile in savedProfiles"
                    :key="profile.id"
                    class="flex cursor-pointer items-center gap-3 rounded-2xl p-3 transition-all hover:bg-muted/50 active:scale-[0.98]"
                    :style="{ borderLeft: `4px solid ${profile.colour}` }"
                >
                    <span class="text-2xl">{{ profile.emoji }}</span>
                    <span class="font-medium text-foreground">{{
                        profile.name
                    }}</span>
                </div>
            </div>

            <EmptyState
                v-else
                emoji="💕"
                title="No saved profiles"
                description="Save your favourite questionnaire answers to reuse them on future date nights."
            />
        </div>
    </div>
</template>
