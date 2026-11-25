<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import SecondaryButton from '@/components/SecondaryButton.vue';
import MobileLayout from '@/layouts/MobileLayout.vue';
import { index, react } from '@/routes/love-notes';

defineOptions({ layout: MobileLayout });

interface NoteItem {
    id: number;
    message: string;
    person_name: string | null;
    person_avatar: string | null;
    direction: 'sent' | 'received';
    reaction: string | null;
    read_at: string | null;
    created_at: string;
}

const props = defineProps<{ note: NoteItem; reactions: string[] }>();

// Tapping the reaction already chosen removes it.
function toggleReaction(emoji: string): void {
    router.post(
        react(props.note.id).url,
        { reaction: props.note.reaction === emoji ? null : emoji },
        { preserveScroll: true },
    );
}

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
    <Head title="Love note" />

    <div class="space-y-8 px-4 pt-6 pb-28">
        <PageHeader
            title="Love note"
            :back-href="index().url"
            back-label="Love notes"
        />

        <article class="card-feature px-6 py-8">
            <p class="eyebrow text-gold-soft">
                {{ formatDate(note.created_at) }}
            </p>

            <!-- Message -->
            <p
                class="mt-4 font-display text-2xl leading-snug font-medium whitespace-pre-line"
            >
                {{ note.message }}
            </p>

            <div class="my-6 hairline-gold opacity-60" />

            <!-- Sender -->
            <div class="flex items-center gap-3.5">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-2xl ring-1 ring-white/20"
                    aria-hidden="true"
                >
                    {{ note.person_avatar || '💕' }}
                </div>
                <p class="min-w-0 truncate font-medium">
                    {{ note.person_name ?? 'Your partner' }}
                </p>
                <span
                    v-if="note.direction === 'sent' && note.reaction"
                    class="ml-auto text-2xl"
                >
                    <span class="sr-only">Reaction:</span>
                    {{ note.reaction }}
                </span>
            </div>
        </article>

        <div
            v-if="note.direction === 'received'"
            class="flex flex-wrap justify-center gap-2"
        >
            <button
                v-for="emoji in reactions"
                :key="emoji"
                type="button"
                class="chip px-3 py-1.5 text-xl"
                :aria-pressed="note.reaction === emoji"
                :aria-label="`React with ${emoji}`"
                @click="toggleReaction(emoji)"
            >
                {{ emoji }}
            </button>
        </div>

        <SecondaryButton
            variant="outline"
            full-width
            @click="router.visit(index().url)"
        >
            Back to love notes
        </SecondaryButton>
    </div>
</template>
