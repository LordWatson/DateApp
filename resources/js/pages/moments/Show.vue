<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import PrimaryButton from '@/components/PrimaryButton.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { index } from '@/routes/moments';

defineOptions({ layout: MobileLayout });

interface Moment {
    id: number;
    title: string;
    description: string | null;
    mood: string | null;
    photo: string | null;
    date: string;
    is_favourite: boolean;
    is_owner: boolean;
    private_notes: string | null;
    tags: string[];
    date_night_plan: { id: number; theme: string } | null;
}

const props = defineProps<{ moment: Moment }>();

function formatDate(dateStr: string): string {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function toggleFavourite(): void {
    router.post(route('moments.toggle-favourite', props.moment.id));
}

function deleteMoment(): void {
    router.delete(route('moments.destroy', props.moment.id));
}
</script>

<template>
    <Head :title="moment.title" />

    <div class="space-y-4 px-4 py-6 pb-24">
        <!-- Back + Actions -->
        <div class="flex items-center justify-between">
            <button
                class="flex items-center gap-1 text-sm font-semibold text-primary transition-opacity hover:opacity-70 focus:outline-none"
                @click="router.visit(route('moments.index'))"
            >
                ← Back
            </button>

            <button
                v-if="moment.is_owner"
                class="text-2xl transition-transform hover:scale-125 focus:outline-none"
                :class="
                    moment.is_favourite
                        ? 'text-warning'
                        : 'text-muted-foreground/40'
                "
                :aria-label="
                    moment.is_favourite
                        ? 'Remove from favourites'
                        : 'Add to favourites'
                "
                @click="toggleFavourite"
            >
                ⭐
            </button>
        </div>

        <!-- Photo -->
        <div
            v-if="moment.photo"
            class="overflow-hidden rounded-3xl shadow-xl"
        >
            <img
                :src="moment.photo"
                :alt="moment.title"
                class="h-72 w-full object-cover"
            />
        </div>

        <!-- Main Card -->
        <div class="card-premium p-6 space-y-4">
            <!-- Title & Mood -->
            <div class="flex items-start gap-3">
                <span
                    v-if="moment.mood"
                    class="text-4xl"
                    aria-hidden="true"
                >{{ moment.mood }}</span>
                <div>
                    <h1 class="text-2xl font-semibold text-foreground leading-tight">
                        {{ moment.title }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ formatDate(moment.date) }}
                    </p>
                </div>
            </div>

            <!-- Description -->
            <p
                v-if="moment.description"
                class="text-foreground/80 leading-relaxed"
            >
                {{ moment.description }}
            </p>

            <!-- Tags -->
            <div
                v-if="moment.tags.length > 0"
                class="flex flex-wrap gap-2"
            >
                <span
                    v-for="tag in moment.tags"
                    :key="tag"
                    class="rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary"
                >
                    #{{ tag }}
                </span>
            </div>

            <!-- Linked Plan -->
            <div
                v-if="moment.date_night_plan"
                class="flex items-center gap-2 rounded-2xl bg-secondary/10 px-4 py-3"
            >
                <span class="text-lg">🌙</span>
                <div>
                    <p class="text-xs text-muted-foreground">Linked date night</p>
                    <p class="text-sm font-semibold text-foreground">
                        {{ moment.date_night_plan.theme }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Private Notes Card -->
        <div
            v-if="moment.is_owner && moment.private_notes"
            class="card-premium p-6 space-y-2"
        >
            <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                🔒 Private Notes
            </h2>
            <p class="text-foreground/80 leading-relaxed">
                {{ moment.private_notes }}
            </p>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-2">
            <SecondaryButton
                variant="outline"
                full-width
                @click="router.visit(index().url)"
            >
                Back to Moments
            </SecondaryButton>
            <PrimaryButton
                v-if="moment.is_owner"
                full-width
                class="bg-destructive hover:bg-destructive/90"
                @click="deleteMoment"
            >
                Delete
            </PrimaryButton>
        </div>
    </div>
</template>
