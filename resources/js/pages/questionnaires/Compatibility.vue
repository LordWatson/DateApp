<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface CompatibilityEntry {
    question: string;
    emoji: string | null;
    user_answer: string;
    partner_answer: string;
    score?: number;
    type: string;
}

interface CompatibilityBreakdown {
    total_questions: number;
    matched_count: number;
    different_count: number;
}

interface CompatibilityData {
    percentage: number;
    matched: CompatibilityEntry[];
    different: CompatibilityEntry[];
    breakdown: CompatibilityBreakdown;
}

interface QuestionnaireData {
    id: number;
    title: string;
    slug: string;
}

interface Props {
    questionnaire: QuestionnaireData;
    partner_name: string | null;
    partner_completed: boolean;
    compatibility: CompatibilityData | null;
}

const props = defineProps<Props>();

const circumference = 2 * Math.PI * 45;
const dashOffset = computed(() => {
    if (!props.compatibility) {
        return circumference;
    }

    return (
        circumference - (props.compatibility.percentage / 100) * circumference
    );
});
</script>

<template>
    <Head title="Compatibility" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            :eyebrow="questionnaire.title"
            title="Compatibility"
            :description="
                partner_name ? `You & ${partner_name}` : 'You & your partner'
            "
        />

        <!-- Waiting state -->
        <div v-if="!partner_completed" class="card-premium">
            <EmptyState
                emoji="⏳"
                title="Waiting for your partner"
                :description="`Once ${partner_name ?? 'your partner'} completes the questionnaire, your compatibility score will appear here.`"
            >
                <SecondaryButton
                    variant="outline"
                    full-width
                    @click="router.visit('/dashboard')"
                >
                    Return home
                </SecondaryButton>
            </EmptyState>
        </div>

        <!-- Compatibility results -->
        <template v-else-if="compatibility">
            <!-- Score circle -->
            <section
                class="card-feature flex flex-col items-center gap-5 p-6 text-center"
            >
                <p class="eyebrow text-gold-soft">Overall match</p>

                <div class="relative size-40">
                    <svg
                        viewBox="0 0 100 100"
                        class="size-full -rotate-90"
                        aria-hidden="true"
                    >
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="5"
                            class="text-white/15"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="5"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="dashOffset"
                            class="text-gold-soft transition-all duration-1000 ease-out"
                        />
                    </svg>
                    <p
                        class="absolute inset-0 flex items-center justify-center font-display text-5xl leading-none font-medium tabular-nums"
                    >
                        {{ compatibility.percentage
                        }}<span class="text-2xl text-white/70">%</span>
                    </p>
                </div>

                <p class="text-sm text-white/80 tabular-nums">
                    {{ compatibility.breakdown.matched_count }} matched ·
                    {{ compatibility.breakdown.different_count }}
                    differed
                </p>
            </section>

            <!-- Matched answers -->
            <section v-if="compatibility.matched.length > 0" class="space-y-3">
                <SectionHeader title="Things you both matched on">
                    <span class="badge-soft tabular-nums">{{
                        compatibility.matched.length
                    }}</span>
                </SectionHeader>
                <div class="divide-y divide-border card-premium px-5 py-1">
                    <div
                        v-for="(entry, i) in compatibility.matched"
                        :key="i"
                        class="flex items-start gap-3.5 py-4"
                    >
                        <span
                            v-if="entry.emoji"
                            class="icon-tile h-11 w-11 text-xl"
                            aria-hidden="true"
                            >{{ entry.emoji }}</span
                        >
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <p
                                class="text-sm leading-snug font-medium text-foreground"
                            >
                                {{ entry.question }}
                            </p>
                            <p
                                v-if="entry.type !== 'text'"
                                class="flex items-start gap-1.5 text-sm leading-snug text-success"
                            >
                                <Check
                                    class="mt-0.5 size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <span>{{ entry.user_answer }}</span>
                            </p>
                            <div
                                v-else
                                class="space-y-1 text-[13px] leading-snug text-muted-foreground"
                            >
                                <p>
                                    <span class="font-medium text-foreground"
                                        >You:</span
                                    >
                                    {{ entry.user_answer }}
                                </p>
                                <p>
                                    <span class="font-medium text-foreground"
                                        >{{ partner_name ?? 'Partner' }}:</span
                                    >
                                    {{ entry.partner_answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Different answers -->
            <section
                v-if="compatibility.different.length > 0"
                class="space-y-3"
            >
                <SectionHeader title="Things that differed">
                    <span class="badge-soft tabular-nums">{{
                        compatibility.different.length
                    }}</span>
                </SectionHeader>
                <div class="divide-y divide-border card-premium px-5 py-1">
                    <div
                        v-for="(entry, i) in compatibility.different"
                        :key="i"
                        class="flex items-start gap-3.5 py-4"
                    >
                        <span
                            v-if="entry.emoji"
                            class="icon-tile h-11 w-11 text-xl"
                            aria-hidden="true"
                            >{{ entry.emoji }}</span
                        >
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <p
                                class="text-sm leading-snug font-medium text-foreground"
                            >
                                {{ entry.question }}
                            </p>
                            <div
                                class="space-y-1 text-[13px] leading-snug text-muted-foreground"
                            >
                                <p>
                                    <span class="font-medium text-foreground"
                                        >You:</span
                                    >
                                    {{ entry.user_answer }}
                                </p>
                                <p>
                                    <span class="font-medium text-foreground"
                                        >{{ partner_name ?? 'Partner' }}:</span
                                    >
                                    {{ entry.partner_answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <PrimaryButton full-width @click="router.visit('/dashboard')">
                Return home
            </PrimaryButton>
        </template>
    </div>
</template>
