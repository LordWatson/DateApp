<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import ProgressBar from '@/components/ProgressBar.vue';
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

    return props.achievements.filter((a) => a.category === selectedCategory.value);
});

const unlocked = computed(() => filtered.value.filter((a) => a.unlocked));
const locked = computed(() => filtered.value.filter((a) => !a.unlocked && !a.hidden));

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function categoryLabel(cat: string): string {
    return cat === 'all' ? 'All' : cat.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>

<template>
    <Head title="Achievements" />

    <div class="space-y-6 px-4 py-6 pb-24">
        <!-- Header -->
        <div class="text-center">
            <div class="mb-2 text-5xl" aria-hidden="true">🏆</div>
            <h1 class="text-2xl font-semibold text-foreground">Achievements</h1>
            <p class="text-sm text-muted-foreground">
                {{ progress.unlocked }} of {{ progress.total }} unlocked · {{ progress.points }} points
            </p>
        </div>

        <!-- Progress Card -->
        <div class="card-premium p-5">
            <div class="mb-3 flex justify-between text-sm font-semibold">
                <span class="text-foreground">Overall Progress</span>
                <span class="text-primary">{{ progress.percentage }}%</span>
            </div>
            <ProgressBar :value="progress.unlocked" :max="progress.total" />
        </div>

        <!-- Category Filter -->
        <div class="flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Filter achievements by category">
            <button
                v-for="cat in categories"
                :key="cat"
                role="tab"
                :aria-selected="selectedCategory === cat"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold capitalize transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                :class="selectedCategory === cat ? 'gradient-primary text-white shadow-md' : 'bg-card text-muted-foreground shadow'"
                @click="selectedCategory = cat"
            >
                {{ categoryLabel(cat) }}
            </button>
        </div>

        <!-- Unlocked -->
        <div v-if="unlocked.length > 0">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Unlocked ✨</h2>
            <div class="space-y-3">
                <div
                    v-for="achievement in unlocked"
                    :key="achievement.id"
                    class="card-premium flex items-center gap-4 p-4"
                >
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-3xl gradient-primary-soft shadow-md">
                        {{ achievement.emoji }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-foreground">{{ achievement.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ achievement.description }}</p>
                        <p v-if="achievement.unlocked_at" class="mt-1 text-xs text-muted-foreground">
                            Unlocked {{ formatDate(achievement.unlocked_at) }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-lg font-semibold text-primary">+{{ achievement.points }}</p>
                        <p class="text-xs text-muted-foreground">pts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Locked -->
        <div v-if="locked.length > 0">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">Locked 🔒</h2>
            <div class="space-y-3">
                <div
                    v-for="achievement in locked"
                    :key="achievement.id"
                    class="card-premium flex items-center gap-4 p-4 opacity-60"
                >
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-muted text-3xl grayscale">
                        {{ achievement.emoji }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-foreground">{{ achievement.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ achievement.description }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-lg font-semibold text-muted-foreground">{{ achievement.points }}</p>
                        <p class="text-xs text-muted-foreground">pts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <EmptyState
            v-if="filtered.length === 0"
            emoji="🏆"
            title="No achievements here"
            description="Keep using the app together to unlock them!"
        />
    </div>
</template>
