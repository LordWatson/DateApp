<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PrimaryButton from '@/components/PrimaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';

defineOptions({ layout: MobileLayout });

interface NoteItem {
    id: number;
    message: string;
    person_name: string | null;
    person_avatar: string | null;
    read_at: string | null;
    created_at: string;
}

defineProps<{ note: NoteItem }>();

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString([], {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Love Note 💌" />

    <div class="min-h-screen px-4 py-6 pb-28">
        <div class="mb-6 flex items-center gap-3">
            <button
                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-card shadow-sm"
                aria-label="Back to love notes"
                @click="router.visit('/love-notes')"
            >
                ←
            </button>
            <h1 class="text-2xl font-semibold text-foreground">💌 Love Note</h1>
        </div>

        <div class="card-premium p-6 space-y-6">
            <!-- Sender -->
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-primary/10 text-3xl">
                    {{ note.person_avatar || '💕' }}
                </div>
                <div>
                    <p class="font-semibold text-foreground">{{ note.person_name ?? 'Your partner' }}</p>
                    <p class="text-xs text-muted-foreground">{{ formatDate(note.created_at) }}</p>
                </div>
            </div>

            <!-- Message -->
            <div class="rounded-3xl bg-primary/5 border border-primary/20 p-5">
                <p class="text-base leading-relaxed text-foreground">{{ note.message }}</p>
            </div>
        </div>

        <div class="mt-6">
            <PrimaryButton full-width @click="router.visit('/love-notes')">
                Back to Love Notes
            </PrimaryButton>
        </div>
    </div>
</template>
