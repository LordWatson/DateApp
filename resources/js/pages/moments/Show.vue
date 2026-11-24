<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, Lock, Moon, Star, Trash2 } from '@lucide/vue';
import { route } from 'ziggy-js';
import PageHeader from '@/components/PageHeader.vue';
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

const focusClass =
    'focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';
</script>

<template>
    <Head :title="moment.title" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <div>
            <!-- Hand-built back control: it keeps the page's original route call -->
            <button
                :class="[
                    'mb-4 -ml-1.5 inline-flex min-h-9 items-center gap-1 rounded-full pr-3 pl-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground',
                    focusClass,
                ]"
                @click="router.visit(route('moments.index'))"
            >
                <ChevronLeft class="size-4" aria-hidden="true" />
                Back
            </button>

            <PageHeader
                :eyebrow="formatDate(moment.date)"
                :title="moment.title"
            >
                <button
                    v-if="moment.is_owner"
                    :class="[
                        'flex size-10 items-center justify-center rounded-full border border-border bg-card transition-colors hover:bg-muted',
                        focusClass,
                        moment.is_favourite
                            ? 'text-gold'
                            : 'text-muted-foreground/60',
                    ]"
                    :aria-label="
                        moment.is_favourite
                            ? 'Remove from favourites'
                            : 'Add to favourites'
                    "
                    @click="toggleFavourite"
                >
                    <Star
                        class="size-5"
                        :class="moment.is_favourite && 'fill-current'"
                        aria-hidden="true"
                    />
                </button>
            </PageHeader>
        </div>

        <!-- Photo -->
        <div v-if="moment.photo" class="overflow-hidden card-premium">
            <img
                :src="moment.photo"
                :alt="moment.title"
                class="h-72 w-full object-cover"
            />
        </div>

        <!-- Story -->
        <div
            v-if="
                moment.mood ||
                moment.description ||
                moment.tags.length > 0 ||
                moment.date_night_plan
            "
            class="space-y-4 card-premium p-6"
        >
            <div v-if="moment.mood" class="flex items-center gap-3.5">
                <span class="icon-tile h-12 w-12 text-2xl" aria-hidden="true">{{
                    moment.mood
                }}</span>
                <p class="eyebrow">Mood</p>
            </div>

            <p
                v-if="moment.description"
                class="text-[15px] leading-relaxed text-foreground/80"
            >
                {{ moment.description }}
            </p>

            <!-- Tags -->
            <div v-if="moment.tags.length > 0" class="flex flex-wrap gap-1.5">
                <span v-for="tag in moment.tags" :key="tag" class="badge-soft">
                    #{{ tag }}
                </span>
            </div>

            <!-- Linked plan -->
            <template v-if="moment.date_night_plan">
                <div class="hairline-gold" />
                <div class="flex items-center gap-3.5">
                    <span class="icon-tile h-11 w-11" aria-hidden="true">
                        <Moon class="size-5 text-gold" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">
                            Linked date night
                        </p>
                        <p class="truncate font-medium text-foreground">
                            {{ moment.date_night_plan.theme }}
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Private notes -->
        <section
            v-if="moment.is_owner && moment.private_notes"
            class="space-y-3 card-premium p-6"
        >
            <h2
                class="flex items-center gap-1.5 font-sans eyebrow text-muted-foreground"
            >
                <Lock class="size-3.5" aria-hidden="true" />
                Private notes
            </h2>
            <p class="text-[15px] leading-relaxed text-foreground/80">
                {{ moment.private_notes }}
            </p>
        </section>

        <!-- Actions -->
        <div class="space-y-3">
            <SecondaryButton
                variant="outline"
                full-width
                @click="router.visit(index().url)"
            >
                Back to moments
            </SecondaryButton>
            <SecondaryButton
                v-if="moment.is_owner"
                variant="ghost"
                full-width
                class="text-destructive! hover:bg-destructive/10!"
                @click="deleteMoment"
            >
                <Trash2 class="size-4" aria-hidden="true" />
                Delete
            </SecondaryButton>
        </div>
    </div>
</template>
