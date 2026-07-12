<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

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
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
}

function categoryLabel(cat: string): string {
    return cat === 'all' ? 'All' : cat.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>

<template>
    <AppLayout>
        <Head title="Achievements" />

        <div class="min-h-screen bg-[#FFF7FB] px-4 py-8">
            <div class="mx-auto max-w-lg">
                <!-- Header -->
                <div class="mb-6 text-center">
                    <div class="mb-2 text-5xl">🏆</div>
                    <h1 class="text-2xl font-semibold text-gray-900">Achievements</h1>
                    <p class="text-sm text-gray-500">{{ progress.unlocked }} of {{ progress.total }} unlocked · {{ progress.points }} points</p>
                </div>

                <!-- Progress Bar -->
                <div class="mb-8 rounded-3xl bg-white p-5 shadow-xl">
                    <div class="mb-3 flex justify-between text-sm font-semibold">
                        <span class="text-gray-700">Overall Progress</span>
                        <span class="text-pink-500">{{ progress.percentage }}%</span>
                    </div>
                    <div class="h-4 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-pink-500 to-purple-600 transition-all duration-700"
                            :style="{ width: `${progress.percentage}%` }"
                        />
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="mb-6 flex gap-2 overflow-x-auto pb-2">
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        class="flex-shrink-0 rounded-full px-4 py-2 text-sm font-semibold capitalize transition-all"
                        :class="selectedCategory === cat ? 'bg-pink-500 text-white shadow-md' : 'bg-white text-gray-600 shadow'"
                        @click="selectedCategory = cat"
                    >
                        {{ categoryLabel(cat) }}
                    </button>
                </div>

                <!-- Unlocked -->
                <div v-if="unlocked.length > 0" class="mb-8">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Unlocked ✨</h2>
                    <div class="space-y-3">
                        <div
                            v-for="achievement in unlocked"
                            :key="achievement.id"
                            class="flex items-center gap-4 rounded-3xl bg-white p-4 shadow-xl"
                        >
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-100 to-purple-100 text-3xl shadow-md">
                                {{ achievement.emoji }}
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">{{ achievement.name }}</div>
                                <div class="text-sm text-gray-500">{{ achievement.description }}</div>
                                <div v-if="achievement.unlocked_at" class="mt-1 text-xs text-gray-400">
                                    Unlocked {{ formatDate(achievement.unlocked_at) }}
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-lg font-semibold text-pink-500">+{{ achievement.points }}</div>
                                <div class="text-xs text-gray-400">pts</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Locked -->
                <div v-if="locked.length > 0">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">Locked 🔒</h2>
                    <div class="space-y-3">
                        <div
                            v-for="achievement in locked"
                            :key="achievement.id"
                            class="flex items-center gap-4 rounded-3xl bg-white p-4 shadow-md opacity-60"
                        >
                            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-3xl grayscale">
                                {{ achievement.emoji }}
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-700">{{ achievement.name }}</div>
                                <div class="text-sm text-gray-400">{{ achievement.description }}</div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-lg font-semibold text-gray-400">{{ achievement.points }}</div>
                                <div class="text-xs text-gray-400">pts</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty -->
                <div v-if="filtered.length === 0" class="py-16 text-center">
                    <div class="mb-4 text-6xl">🏆</div>
                    <h3 class="text-xl font-semibold text-gray-900">No achievements here</h3>
                    <p class="mt-2 text-gray-500">Keep using the app to unlock them!</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
