<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronRight, Clock, UserPlus } from '@lucide/vue';
import { computed } from 'vue';
import PartnerController from '@/actions/App/Http/Controllers/PartnerController';
import ChallengeCard from '@/components/ChallengeCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import SectionHeader from '@/components/SectionHeader.vue';
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
    <Head title="Home" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <!-- Hero -->
        <section class="card-feature p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 space-y-1.5">
                    <p class="eyebrow text-gold-soft">{{ greeting }}</p>
                    <h1 class="truncate text-4xl leading-tight font-medium">
                        {{ firstName }}
                    </h1>
                </div>
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-3xl ring-1 ring-white/20"
                    aria-hidden="true"
                >
                    {{ user.avatar || '💕' }}
                </div>
            </div>

            <button
                type="button"
                class="mt-4 inline-flex max-w-full items-center gap-2 rounded-full bg-white/10 py-1.5 pr-3 pl-2 text-left text-xs font-medium text-white/90 ring-1 ring-white/15 transition-colors hover:bg-white/15"
                @click="invitePartner"
            >
                <template v-if="partnerStatus === 'connected'">
                    <span class="text-base leading-none" aria-hidden="true">{{
                        partner?.avatar || '💕'
                    }}</span>
                    <span class="truncate"
                        >With {{ partner?.display_name ?? partner?.name }}</span
                    >
                    <span
                        class="size-1.5 shrink-0 rounded-full bg-success"
                        aria-hidden="true"
                    />
                </template>
                <template v-else-if="partnerStatus === 'pending'">
                    <Clock class="size-3.5" aria-hidden="true" />
                    <span class="truncate">Invitation pending</span>
                </template>
                <template v-else>
                    <UserPlus class="size-3.5" aria-hidden="true" />
                    <span>Invite your partner</span>
                </template>
            </button>

            <div class="my-5 hairline-gold opacity-60" />

            <Link
                href="/questionnaires"
                class="group flex min-h-12 w-full items-center justify-between rounded-full bg-white pr-2 pl-6 text-[15px] font-semibold tracking-tight text-primary shadow-lg transition-all duration-200 hover:-translate-y-px active:scale-[0.98]"
            >
                Plan tonight's date
                <span
                    class="flex size-9 items-center justify-center rounded-full text-white transition-transform gradient-primary group-hover:translate-x-0.5"
                >
                    <ArrowRight class="size-4" aria-hidden="true" />
                </span>
            </Link>
        </section>

        <!-- Stats -->
        <section class="grid grid-cols-3 gap-3" aria-label="Your streaks">
            <StatCard
                label="Streak"
                :value="String(stats.current_streak)"
                description="days"
            />
            <StatCard
                label="Best"
                :value="String(stats.longest_streak)"
                description="days"
            />
            <StatCard
                :label="
                    new Date().toLocaleString('default', { month: 'short' })
                "
                :value="String(stats.monthly_completions)"
                description="completed"
            />
        </section>

        <!-- Today's challenge -->
        <ChallengeCard
            v-if="todayChallenge"
            :title="todayChallenge.title"
            :description="todayChallenge.description ?? ''"
            :emoji="todayChallenge.emoji"
            :category="todayChallenge.difficulty"
        />
        <div v-else class="card-premium">
            <EmptyState
                emoji="✨"
                title="No challenge today"
                description="Check back soon for your daily romantic challenge."
            />
        </div>

        <!-- Love notes -->
        <Link
            href="/love-notes"
            class="flex card-hover items-center gap-4 card-premium p-4"
        >
            <div class="icon-tile h-12 w-12 text-2xl" aria-hidden="true">
                💌
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="text-lg leading-tight font-semibold text-foreground">
                    Love notes
                </h2>
                <p class="text-sm text-muted-foreground">
                    Send a sweet message to your partner
                </p>
            </div>
            <ChevronRight
                class="size-5 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
        </Link>

        <!-- Recent date nights -->
        <section class="space-y-3">
            <SectionHeader
                title="Recent date nights"
                action-label="View all"
                action-href="/date-night/history"
            />

            <div class="card-premium p-2">
                <template v-if="recentDateNights.length > 0">
                    <Link
                        v-for="plan in recentDateNights"
                        :key="plan.id"
                        :href="`/date-night/${plan.id}`"
                        class="list-row"
                    >
                        <span
                            class="icon-tile h-11 w-11 text-xl"
                            aria-hidden="true"
                            >{{ plan.theme_emoji || '💖' }}</span
                        >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-foreground">
                                {{ plan.theme || 'Date night' }}
                            </p>
                            <p
                                v-if="plan.created_at"
                                class="text-xs text-muted-foreground"
                            >
                                {{
                                    new Date(
                                        plan.created_at,
                                    ).toLocaleDateString(undefined, {
                                        day: 'numeric',
                                        month: 'short',
                                        year: 'numeric',
                                    })
                                }}
                            </p>
                        </div>
                        <span
                            v-if="plan.compatibility_score !== null"
                            class="badge-soft tabular-nums"
                            >{{ plan.compatibility_score }}% match</span
                        >
                        <ChevronRight
                            class="size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </Link>
                </template>

                <EmptyState
                    v-else
                    emoji="💖"
                    title="No date nights yet"
                    description="Complete a questionnaire with your partner to generate your first date night plan."
                />
            </div>
        </section>

        <!-- Saved profiles -->
        <section v-if="savedProfiles.length > 0" class="space-y-3">
            <SectionHeader title="Saved profiles" />

            <div class="card-premium p-2">
                <div
                    v-for="profile in savedProfiles"
                    :key="profile.id"
                    class="flex items-center gap-3.5 p-3"
                >
                    <span
                        class="icon-tile h-11 w-11 text-xl"
                        :style="{
                            boxShadow: `inset 0 0 0 1.5px ${profile.colour}`,
                        }"
                        aria-hidden="true"
                        >{{ profile.emoji }}</span
                    >
                    <span class="font-medium text-foreground">{{
                        profile.name
                    }}</span>
                </div>
            </div>
        </section>
    </div>
</template>
