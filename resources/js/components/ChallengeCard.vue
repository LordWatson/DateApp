<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Circle, Flame } from '@lucide/vue';
import { ref } from 'vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import { complete } from '@/routes/daily-challenge';

interface Props {
    title: string;
    description?: string;
    emoji?: string;
    difficulty?: string;
    completedByMe?: boolean;
    /** null when there is no partner to show */
    completedByPartner?: boolean | null;
    partnerName?: string;
    /** Days in a row both partners did the challenge; null without a partner. */
    coupleStreak?: number | null;
}

const {
    completedByMe = false,
    completedByPartner = null,
    coupleStreak = null,
} = defineProps<Props>();

const submitting = ref(false);

function markDone(): void {
    router.post(
        complete().url,
        {},
        {
            preserveScroll: true,
            onStart: () => (submitting.value = true),
            onFinish: () => (submitting.value = false),
        },
    );
}
</script>

<template>
    <div class="space-y-4 card-premium p-6">
        <div class="flex items-center justify-between gap-3">
            <p class="eyebrow">Today's challenge</p>
            <span
                v-if="coupleStreak"
                class="badge-soft inline-flex items-center gap-1 tabular-nums"
            >
                <Flame class="size-3.5 text-gold" aria-hidden="true" />
                {{ coupleStreak }} day{{ coupleStreak === 1 ? '' : 's' }}
                together
            </span>
        </div>

        <div class="flex items-start gap-4">
            <span
                v-if="emoji"
                class="icon-tile h-14 w-14 text-3xl"
                aria-hidden="true"
                >{{ emoji }}</span
            >
            <div class="space-y-1.5">
                <h3 class="text-xl leading-snug font-semibold text-foreground">
                    {{ title }}
                </h3>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
        </div>

        <div v-if="difficulty" class="flex items-center gap-2 text-xs">
            <span class="text-muted-foreground">Difficulty</span>
            <span class="badge-soft capitalize">
                {{ difficulty }}
            </span>
        </div>

        <div
            class="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4"
        >
            <ul class="space-y-1 text-sm">
                <li
                    :class="[
                        'flex items-center gap-1.5',
                        completedByMe
                            ? 'text-success'
                            : 'text-muted-foreground',
                    ]"
                >
                    <component
                        :is="completedByMe ? Check : Circle"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ completedByMe ? 'You have done it' : 'You: not yet' }}
                </li>
                <li
                    v-if="completedByPartner !== null"
                    :class="[
                        'flex items-center gap-1.5',
                        completedByPartner
                            ? 'text-success'
                            : 'text-muted-foreground',
                    ]"
                >
                    <component
                        :is="completedByPartner ? Check : Circle"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ partnerName ?? 'Your partner' }}:
                    {{ completedByPartner ? 'done' : 'not yet' }}
                </li>
            </ul>

            <p
                v-if="completedByMe && completedByPartner"
                class="text-sm font-medium text-success"
                role="status"
            >
                Both done. See you tomorrow!
            </p>

            <PrimaryButton
                v-if="!completedByMe"
                size="sm"
                :loading="submitting"
                @click="markDone"
            >
                <Check class="size-4" aria-hidden="true" />
                Done
            </PrimaryButton>
        </div>
    </div>
</template>
