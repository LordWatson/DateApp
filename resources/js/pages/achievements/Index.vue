<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface Achievement {
    id: number;
    name: string;
    description: string;
    emoji: string;
    category: string;
    points: number;
    hidden: boolean;
    unlocked: boolean;
    unlocked_at: string | null;
}

interface Progress {
    total: number;
    unlocked: number;
    points: number;
    percentage: number;
}

const props = defineProps<{
    achievements: Achievement[];
    progress: Progress;
}>();

const selectedCategory = ref('all');

const categories = computed(() => {
    const cats = [...new Set(props.achievements.map((a) => a.category))];

    return ['all', ...cats];
});

const filtered = computed(() => {
    if (selectedCategory.value === 'all') {
        return props.achievements;
    }

    return props.achievements.filter(
        (a) => a.category === selectedCategory.value,
    );
});

const unlocked = computed(() => filtered.value.filter((a) => a.unlocked));
const locked = computed(() =>
    filtered.value.filter((a) => !a.unlocked && !a.hidden),
);

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function categoryLabel(cat: string): string {
    const label = cat.replace(/_/g, ' ');

    return label.charAt(0).toUpperCase() + label.slice(1);
}
</script>

<template>
    <Head title="Achievements" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            eyebrow="Milestones"
            title="Achievements"
            :description="`${progress.unlocked} of ${progress.total} unlocked · ${progress.points} points`"
        />

        <!-- Overall progress -->
        <section class="card-feature p-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="eyebrow text-gold-soft">Overall progress</p>
                    <p
                        class="mt-2 font-display text-6xl leading-none font-medium tabular-nums"
                    >
                        {{ progress.percentage }}%
                    </p>
                </div>
                <p class="text-right text-xs text-white/80">
                    <span
                        class="block font-display text-3xl leading-none font-medium text-white tabular-nums"
                        >{{ progress.points }}</span
                    >
                    points
                </p>
            </div>

            <!-- ProgressBar's track and fill are built for light surfaces, so the bar is drawn here -->
            <div
                class="mt-5 h-1.5 w-full overflow-hidden rounded-full bg-white/15"
                role="progressbar"
                aria-label="Overall progress"
                :aria-valuenow="progress.unlocked"
                :aria-valuemin="0"
                :aria-valuemax="progress.total"
            >
                <div
                    class="h-full rounded-full bg-gold-soft transition-all duration-700 ease-out"
                    :style="{
                        width: `${Math.min(100, Math.max(0, progress.percentage))}%`,
                    }"
                />
            </div>
        </section>

        <!-- Category filter -->
        <div
            class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1"
            role="tablist"
            aria-label="Filter achievements by category"
        >
            <button
                v-for="cat in categories"
                :key="cat"
                type="button"
                role="tab"
                :aria-selected="selectedCategory === cat"
                class="chip focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                @click="selectedCategory = cat"
            >
                {{ categoryLabel(cat) }}
            </button>
        </div>

        <!-- Unlocked -->
        <section v-if="unlocked.length > 0" class="space-y-3">
            <SectionHeader title="Unlocked" />

            <div class="card-premium p-2">
                <div
                    v-for="achievement in unlocked"
                    :key="achievement.id"
                    class="flex items-center gap-3.5 p-3"
                >
                    <span
                        class="icon-tile h-12 w-12 text-2xl"
                        aria-hidden="true"
                        >{{ achievement.emoji }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-foreground">
                            {{ achievement.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ achievement.description }}
                        </p>
                        <p
                            v-if="achievement.unlocked_at"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Unlocked {{ formatDate(achievement.unlocked_at) }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p
                            class="font-display text-xl leading-tight font-medium text-primary tabular-nums"
                        >
                            +{{ achievement.points }}
                        </p>
                        <p class="text-xs text-muted-foreground">pts</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Locked -->
        <section v-if="locked.length > 0" class="space-y-3">
            <SectionHeader title="Locked" />

            <div class="card-premium p-2">
                <div
                    v-for="achievement in locked"
                    :key="achievement.id"
                    class="flex items-center gap-3.5 p-3 opacity-60"
                >
                    <span
                        class="icon-tile h-12 w-12 text-2xl grayscale"
                        aria-hidden="true"
                        >{{ achievement.emoji }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-foreground">
                            {{ achievement.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ achievement.description }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p
                            class="font-display text-xl leading-tight font-medium text-muted-foreground tabular-nums"
                        >
                            {{ achievement.points }}
                        </p>
                        <p class="text-xs text-muted-foreground">pts</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Empty -->
        <div v-if="filtered.length === 0" class="card-premium">
            <EmptyState
                emoji="🏆"
                title="No achievements here"
                description="Keep using the app together to unlock them!"
            />
        </div>
    </div>
</template>
