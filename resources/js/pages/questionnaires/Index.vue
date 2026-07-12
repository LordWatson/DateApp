<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface Questionnaire {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    emoji: string | null;
    estimated_minutes: number | null;
    question_count: number;
    response_status: string | null;
}

interface Props {
    questionnaires: Questionnaire[];
}

defineProps<Props>();

function statusLabel(status: string | null): string {
    if (status === 'completed') {
        return 'Completed ✓';
    }

    if (status === 'in_progress') {
        return 'In Progress…';
    }

    return 'Not started';
}

function statusClass(status: string | null): string {
    if (status === 'completed') {
        return 'text-success';
    }

    if (status === 'in_progress') {
        return 'text-warning';
    }

    return 'text-muted-foreground';
}

function openQuestionnaire(slug: string): void {
    router.visit(`/questionnaires/${slug}`);
}
</script>

<template>
    <Head title="Questionnaires" />

    <div class="space-y-4 px-4 py-6 pb-24">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-foreground">Questionnaires 📋</h1>
            <p class="text-sm text-muted-foreground">Choose a questionnaire to plan your evening.</p>
        </div>

        <div v-if="questionnaires.length === 0" class="card-premium p-8 text-center">
            <p class="text-4xl">📭</p>
            <p class="mt-3 font-semibold text-foreground">No questionnaires available</p>
            <p class="mt-1 text-sm text-muted-foreground">Check back soon!</p>
        </div>

        <div
            v-for="q in questionnaires"
            :key="q.id"
            class="card-premium cursor-pointer p-6 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99]"
            @click="openQuestionnaire(q.slug)"
        >
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-3xl">
                    {{ q.emoji ?? '💕' }}
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold text-foreground">{{ q.title }}</h2>
                    <p v-if="q.description" class="mt-0.5 text-sm text-muted-foreground line-clamp-2">{{ q.description }}</p>
                    <div class="mt-2 flex items-center gap-3 text-xs">
                        <span class="text-muted-foreground">{{ q.question_count }} questions</span>
                        <span v-if="q.estimated_minutes" class="text-muted-foreground">~{{ q.estimated_minutes }} min</span>
                        <span :class="statusClass(q.response_status)" class="font-medium">{{ statusLabel(q.response_status) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
